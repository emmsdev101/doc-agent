<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChatRequest;
use App\Models\ChatSession;
use App\Models\KnowledgeBase;
use App\Services\Rag\RagChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function store(ChatRequest $request, RagChatService $rag): StreamedResponse|JsonResponse
    {
        /** @var KnowledgeBase $knowledgeBase */
        $knowledgeBase = $request->attributes->get('knowledgeBase');

        $session = $this->session($request, $knowledgeBase);
        $question = trim((string) $request->validated('message'));

        if (! $request->boolean('stream', true)) {
            $result = $rag->answer($knowledgeBase, $session, $question);

            return response()->json([
                'session_id' => $session->id,
                'answer' => $result['answer'],
                'citations' => $result['citations'],
            ]);
        }

        return response()->stream(function () use ($rag, $knowledgeBase, $session, $question): void {
            $this->emit('meta', ['session_id' => $session->id]);

            $result = $rag->streamAnswer(
                $knowledgeBase,
                $session,
                $question,
                function (string $token): void {
                    $this->emit('token', ['token' => $token]);
                }
            );

            $this->emit('done', [
                'session_id' => $session->id,
                'citations' => $result['citations'],
            ]);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
            'X-Session-Id' => $session->id,
        ]);
    }

    private function session(Request $request, KnowledgeBase $knowledgeBase): ChatSession
    {
        $sessionId = $request->validated('session_id');

        if (is_string($sessionId) && $sessionId !== '') {
            $existing = ChatSession::query()
                ->where('id', $sessionId)
                ->where('knowledge_base_id', $knowledgeBase->id)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return ChatSession::query()->create([
            'knowledge_base_id' => $knowledgeBase->id,
            'origin' => $request->headers->get('Origin'),
            'visitor_ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function emit(string $event, array $payload): void
    {
        echo "event: {$event}\n";
        echo 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE)."\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}
