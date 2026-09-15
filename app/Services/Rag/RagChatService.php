<?php

namespace App\Services\Rag;

use App\Enums\ChatRole;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\KnowledgeBase;
use App\Services\Embeddings\GenerateEmbeddingService;
use App\Services\Llm\DeepSeekClient;
use Illuminate\Support\Collection;
use RuntimeException;

final class RagChatService
{
    public function __construct(
        private readonly GenerateEmbeddingService $embeddings,
        private readonly VectorSearchService $search,
        private readonly DeepSeekClient $deepSeek,
    ) {
    }

    /**
     * @return array{session: ChatSession, answer: string, citations: list<array<string, mixed>>}
     */
    public function answer(KnowledgeBase $knowledgeBase, ChatSession $session, string $question): array
    {
        $chunks = $this->retrieve($knowledgeBase, $question);
        $messages = $this->prompt($knowledgeBase, $chunks, $question);
        $answer = $this->complete($messages);
        $citations = $this->citations($chunks);

        $this->persist($session, $question, $answer, $citations);

        return [
            'session' => $session,
            'answer' => $answer,
            'citations' => $citations,
        ];
    }

    /**
     * @param  callable(string): void  $onDelta
     * @return array{session: ChatSession, answer: string, citations: list<array<string, mixed>>}
     */
    public function streamAnswer(KnowledgeBase $knowledgeBase, ChatSession $session, string $question, callable $onDelta): array
    {
        $chunks = $this->retrieve($knowledgeBase, $question);
        $messages = $this->prompt($knowledgeBase, $chunks, $question);
        $answer = $this->complete($messages, $onDelta);
        $citations = $this->citations($chunks);

        $this->persist($session, $question, $answer, $citations);

        return [
            'session' => $session,
            'answer' => $answer,
            'citations' => $citations,
        ];
    }

    public function retrieve(KnowledgeBase $knowledgeBase, string $question): Collection
    {
        $embedding = $this->embeddings->embed($question);

        return $this->search->similarChunks(
            $knowledgeBase->id,
            $embedding,
            (int) config('docagent.rag.top_k', 5),
            (float) config('docagent.rag.min_similarity', 0.08),
            $question,
        );
    }

    /**
     * @param  Collection<int, object>  $chunks
     * @return list<array{role: string, content: string}>
     */
    public function prompt(KnowledgeBase $knowledgeBase, Collection $chunks, string $question): array
    {
        $context = $chunks
            ->map(function (object $chunk, int $index): string {
                $source = $chunk->original_filename ?? 'document';

                return '['.($index + 1).'] ('.$source.")\n".$chunk->content;
            })
            ->implode("\n\n");

        if ($context === '') {
            $context = 'No relevant knowledge base passages were retrieved.';
        }

        $system = $knowledgeBase->system_prompt ?: (string) config('docagent.rag.system_prompt');

        return [
            ['role' => 'system', 'content' => $system],
            [
                'role' => 'user',
                'content' => "Context:\n{$context}\n\nQuestion: {$question}",
            ],
        ];
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  (callable(string): void)|null  $onDelta
     */
    private function complete(array $messages, ?callable $onDelta = null): string
    {
        if (blank(config('services.deepseek.key'))) {
            $fallback = $this->localFallbackAnswer($messages);

            if ($onDelta !== null) {
                $onDelta($fallback);
            }

            return $fallback;
        }
        if ($onDelta === null) {
            $response = $this->deepSeek->chat()->post('/chat/completions', [
                'model' => config('services.deepseek.chat_model'),
                'messages' => $messages,
                'temperature' => 0.2,
                'stream' => false,
            ])->throw();

            $answer = $response->json('choices.0.message.content');

            if (! is_string($answer) || $answer === '') {
                throw new RuntimeException('DeepSeek returned an empty completion.');
            }

            return $answer;
        }

        $response = $this->deepSeek->chat()
            ->withOptions(['stream' => true])
            ->post('/chat/completions', [
                'model' => config('services.deepseek.chat_model'),
                'messages' => $messages,
                'temperature' => 0.2,
                'stream' => true,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('DeepSeek streaming request failed: '.$response->body());
        }

        $answer = '';
        $buffer = '';
        $body = $response->toPsrResponse()->getBody();

        while (! $body->eof()) {
            $buffer .= $body->read(1024);

            while (($position = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $position));
                $buffer = substr($buffer, $position + 1);

                if ($line === '' || ! str_starts_with($line, 'data:')) {
                    continue;
                }

                $payload = trim(substr($line, 5));

                if ($payload === '[DONE]') {
                    return $answer;
                }

                $json = json_decode($payload, true);
                $delta = $json['choices'][0]['delta']['content'] ?? '';

                if (is_string($delta) && $delta !== '') {
                    $answer .= $delta;
                    $onDelta($delta);
                }
            }
        }

        return $answer;
    }

    /**
     * @param  Collection<int, object>  $chunks
     * @return list<array<string, mixed>>
     */
    private function citations(Collection $chunks): array
    {
        return $chunks
            ->take(5)
            ->map(fn (object $chunk): array => [
                'document' => $chunk->original_filename,
                'similarity' => round((float) $chunk->similarity, 4),
                'excerpt' => mb_substr($chunk->content, 0, 180),
            ])
            ->all();
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     */
    private function localFallbackAnswer(array $messages): string
    {
        $userMessage = collect($messages)->last()['content'] ?? '';

        if (str_contains($userMessage, 'No relevant knowledge base passages were retrieved.')) {
            return "I don't know. There is no matching context in this knowledge base yet, and DeepSeek is not configured.";
        }

        return "I don't have a DeepSeek API key configured, so I cannot generate a model answer. The retrieved context is:\n\n".$userMessage;
    }

    /**
     * @param  list<array<string, mixed>>  $citations
     */
    private function persist(ChatSession $session, string $question, string $answer, array $citations): void
    {
        ChatMessage::query()->create([
            'chat_session_id' => $session->id,
            'role' => ChatRole::User,
            'content' => $question,
        ]);

        ChatMessage::query()->create([
            'chat_session_id' => $session->id,
            'role' => ChatRole::Assistant,
            'content' => $answer,
            'citations' => $citations,
        ]);
    }
}
