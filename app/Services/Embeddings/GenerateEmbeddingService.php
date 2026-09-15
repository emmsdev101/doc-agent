<?php

namespace App\Services\Embeddings;

use App\Enums\EmbeddingDriver;
use App\Services\Llm\DeepSeekClient;
use Illuminate\Http\Client\RequestException;
use RuntimeException;

final class GenerateEmbeddingService
{
    public function __construct(
        private readonly DeepSeekClient $client,
        private readonly LocalEmbeddingGenerator $local,
    ) {
    }

    /**
     * @return list<float>
     */
    public function embed(string $text): array
    {
        return $this->embedMany(
            [$text],
            (string) config('services.embeddings.query_task', 'retrieval.query'),
        )[0];
    }

    /**
     * @param  list<string>  $texts
     * @return list<list<float>>
     */
    public function embedMany(array $texts, ?string $task = null): array
    {
        $texts = array_values($texts);

        if ($texts === []) {
            return [];
        }

        $driver = EmbeddingDriver::tryFrom((string) config('services.embeddings.driver', 'local'))
            ?? EmbeddingDriver::Local;

        if (! $driver->usesRemoteApi()) {
            return array_map(fn (string $text): array => $this->local->embed($text), $texts);
        }

        $task ??= (string) config('services.embeddings.passage_task', 'retrieval.passage');
        $batchSize = max(1, (int) config('services.embeddings.batch_size', 16));
        $vectors = [];

        foreach (array_chunk($texts, $batchSize) as $batch) {
            $vectors = array_merge($vectors, $this->embedRemoteBatch($batch, $task));
        }

        return $vectors;
    }

    public function dimensions(): int
    {
        return (int) config('services.embeddings.dimensions', 1536);
    }

    /**
     * @param  list<string>  $batch
     * @return list<list<float>>
     */
    private function embedRemoteBatch(array $batch, string $task): array
    {
        $payload = [
            'model' => config('services.embeddings.model'),
            'input' => $batch,
        ];

        if (str_contains((string) config('services.embeddings.base_url'), 'jina.ai')) {
            $payload['task'] = $task;
            $payload['dimensions'] = $this->dimensions();
            $payload['normalized'] = true;
            $payload['truncate'] = true;
        }

        try {
            $response = $this->client->embeddings()
                ->post('/embeddings', $payload)
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException(
                'Embedding API request failed: '.$exception->getMessage(),
                previous: $exception,
            );
        }

        $data = $response->json('data');

        if (! is_array($data)) {
            throw new RuntimeException('Embedding API returned an unexpected payload.');
        }

        usort($data, fn (array $left, array $right): int => ($left['index'] ?? 0) <=> ($right['index'] ?? 0));

        $vectors = [];

        foreach ($data as $item) {
            $embedding = $item['embedding'] ?? null;

            if (! is_array($embedding)) {
                throw new RuntimeException('Embedding API omitted a vector.');
            }

            $vectors[] = array_map('floatval', $embedding);
        }

        if (count($vectors) !== count($batch)) {
            throw new RuntimeException('Embedding API returned fewer vectors than requested.');
        }

        return $vectors;
    }
}
