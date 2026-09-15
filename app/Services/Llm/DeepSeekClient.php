<?php

namespace App\Services\Llm;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class DeepSeekClient
{
    public function chat(): PendingRequest
    {
        $key = (string) config('services.deepseek.key');

        if ($key === '') {
            throw new RuntimeException('DeepSeek API key is not configured. Set DEEPSEEK_API_KEY in your environment.');
        }

        return $this->retrying(Http::baseUrl($this->chatBaseUrl())->withToken($key))
            ->timeout((int) config('services.deepseek.timeout', 60));
    }

    public function embeddings(): PendingRequest
    {
        $key = (string) config('services.embeddings.key');

        if ($key === '') {
            throw new RuntimeException('Embedding API key is not configured.');
        }

        return $this->retrying(Http::baseUrl($this->embeddingBaseUrl())->withToken($key))
            ->timeout((int) config('services.embeddings.timeout', 60));
    }

    private function retrying(PendingRequest $request): PendingRequest
    {
        return $request
            ->acceptJson()
            ->retry(
                4,
                fn (int $attempt): int => (int) (1000 * (2 ** ($attempt - 1))),
                function ($exception): bool {
                    if ($exception instanceof RequestException && $exception->response) {
                        $status = $exception->response->status();

                        return $status >= 500 || $status === 429;
                    }

                    return true;
                },
            );
    }

    private function chatBaseUrl(): string
    {
        return $this->normalizeBaseUrl((string) config('services.deepseek.base_url'));
    }

    private function embeddingBaseUrl(): string
    {
        return $this->normalizeBaseUrl((string) config('services.embeddings.base_url'));
    }

    private function normalizeBaseUrl(string $url): string
    {
        $url = rtrim($url, '/');

        return str_ends_with($url, '/v1') ? $url : $url.'/v1';
    }
}
