<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\Documents\DocumentChunker;
use App\Services\Documents\TextExtractorService;
use App\Services\Embeddings\GenerateEmbeddingService;
use App\Services\Rag\VectorSearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [30, 60, 120, 300];

    public int $timeout = 300;

    public bool $failOnTimeout = true;

    public function __construct(
        public readonly Document $document,
    ) {
        $this->onQueue('documents');
    }

    public function handle(
        TextExtractorService $extractor,
        DocumentChunker $chunker,
        GenerateEmbeddingService $embeddings,
        VectorSearchService $vectors,
    ): void {
        $document = $this->document->fresh();

        if ($document === null) {
            return;
        }

        $document->markProcessing();

        $text = $document->withLocalFile(
            fn (string $path) => $extractor->extract($path, $document->extension),
        );
        $chunks = $chunker->chunk($text);

        if ($chunks === []) {
            throw new \RuntimeException('The document did not produce any chunks.');
        }

        $vectorsPayload = $embeddings->embedMany(array_column($chunks, 'content'));

        DB::transaction(function () use ($document, $chunks, $vectorsPayload, $vectors): void {
            $document->chunks()->delete();

            foreach ($chunks as $index => $chunk) {
                $vectors->insertChunk(
                    $document->id,
                    $document->knowledge_base_id,
                    $chunk,
                    $vectorsPayload[$index],
                    $document->original_filename,
                    $document->extension,
                );
            }

            $document->markProcessed(count($chunks));
        });
    }

    public function failed(?Throwable $exception): void
    {
        $this->document->refresh()->markFailed(
            $exception?->getMessage() ?: 'Document processing failed after retries.'
        );
    }
}
