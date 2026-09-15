<?php

namespace App\Services\Rag;

use App\Services\Embeddings\LocalEmbeddingGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class VectorSearchService
{
    /**
     * @param  list<float>  $embedding
     * @return Collection<int, object>
     */
    public function similarChunks(
        string $knowledgeBaseId,
        array $embedding,
        int $limit,
        float $minSimilarity,
        ?string $queryText = null,
    ): Collection {
        $candidateLimit = max($limit * 10, 50);
        $rows = $this->usesPgVector()
            ? $this->searchWithPgVector($knowledgeBaseId, $embedding, $candidateLimit)
            : $this->searchInPhp($knowledgeBaseId, $embedding, $candidateLimit);

        $scored = collect($rows)
            ->map(function (object $row) use ($queryText): object {
                $cosine = (float) $row->similarity;
                $lexical = is_string($queryText)
                    ? LocalEmbeddingGenerator::tokenRecall($queryText, (string) $row->content)
                    : 0.0;
                $row->similarity = max($cosine, $lexical);
                $row->metadata = is_string($row->metadata) ? json_decode($row->metadata, true) : $row->metadata;

                return $row;
            })
            ->sortByDesc(fn (object $row): float => $row->similarity)
            ->values();

        $matched = $scored
            ->filter(fn (object $row): bool => $row->similarity >= $minSimilarity)
            ->take($limit)
            ->values();

        if ($matched->isNotEmpty()) {
            return $matched;
        }

        return $scored
            ->filter(fn (object $row): bool => $row->similarity > 0)
            ->take($limit)
            ->values();
    }

    /**
     * @param  list<float>  $embedding
     */
    public function insertChunk(
        string $documentId,
        string $knowledgeBaseId,
        array $chunk,
        array $embedding,
        string $filename,
        string $extension,
    ): void {
        $now = now();
        $cast = $this->usesPgVector() ? 'vector' : 'json';

        DB::insert(
            <<<SQL
            INSERT INTO document_chunks (
                id, document_id, knowledge_base_id, content, chunk_index, token_count, metadata, embedding, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?::json, ?::{$cast}, ?, ?)
            SQL,
            [
                (string) Str::uuid(),
                $documentId,
                $knowledgeBaseId,
                $chunk['content'],
                $chunk['index'],
                $chunk['token_count'],
                json_encode([
                    'filename' => $filename,
                    'extension' => $extension,
                ]),
                $this->usesPgVector() ? $this->toPgVector($embedding) : json_encode($embedding),
                $now,
                $now,
            ]
        );
    }

    /**
     * @param  list<float>  $embedding
     */
    public function toPgVector(array $embedding): string
    {
        return '['.implode(',', array_map(
            static fn (float $value): string => sprintf('%.8f', $value),
            $embedding
        )).']';
    }

    public function usesPgVector(): bool
    {
        return (bool) cache()->remember('docagent.pgvector', 60, function (): bool {
            try {
                return DB::selectOne("SELECT 1 AS present FROM pg_extension WHERE extname = 'vector'") !== null
                    && Schema::getColumnType('document_chunks', 'embedding') !== 'json'
                    && Schema::getColumnType('document_chunks', 'embedding') !== 'jsonb';
            } catch (\Throwable) {
                return false;
            }
        });
    }

    /**
     * @param  list<float>  $embedding
     * @return list<object>
     */
    private function searchWithPgVector(string $knowledgeBaseId, array $embedding, int $limit): array
    {
        $vector = $this->toPgVector($embedding);

        return DB::select(
            <<<'SQL'
            SELECT
                document_chunks.id,
                document_chunks.document_id,
                document_chunks.content,
                document_chunks.metadata,
                documents.original_filename,
                1 - (document_chunks.embedding <=> ?::vector) AS similarity
            FROM document_chunks
            INNER JOIN documents ON documents.id = document_chunks.document_id
            WHERE document_chunks.knowledge_base_id = ?
              AND document_chunks.embedding IS NOT NULL
            ORDER BY document_chunks.embedding <=> ?::vector
            LIMIT ?
            SQL,
            [$vector, $knowledgeBaseId, $vector, $limit]
        );
    }

    /**
     * @param  list<float>  $embedding
     * @return list<object>
     */
    private function searchInPhp(string $knowledgeBaseId, array $embedding, int $limit): array
    {
        $rows = DB::select(
            <<<'SQL'
            SELECT
                document_chunks.id,
                document_chunks.document_id,
                document_chunks.content,
                document_chunks.metadata,
                document_chunks.embedding,
                documents.original_filename
            FROM document_chunks
            INNER JOIN documents ON documents.id = document_chunks.document_id
            WHERE document_chunks.knowledge_base_id = ?
              AND document_chunks.embedding IS NOT NULL
            SQL,
            [$knowledgeBaseId]
        );

        $scored = [];

        foreach ($rows as $row) {
            $stored = is_string($row->embedding) ? json_decode($row->embedding, true) : $row->embedding;
            if (! is_array($stored)) {
                continue;
            }

            $row->similarity = $this->cosine($embedding, array_map('floatval', $stored));
            unset($row->embedding);
            $scored[] = $row;
        }

        usort($scored, fn (object $left, object $right): int => $right->similarity <=> $left->similarity);

        return array_slice($scored, 0, $limit);
    }

    /**
     * @param  list<float>  $left
     * @param  list<float>  $right
     */
    private function cosine(array $left, array $right): float
    {
        $dot = 0.0;
        $leftNorm = 0.0;
        $rightNorm = 0.0;
        $count = min(count($left), count($right));

        for ($i = 0; $i < $count; $i++) {
            $dot += $left[$i] * $right[$i];
            $leftNorm += $left[$i] * $left[$i];
            $rightNorm += $right[$i] * $right[$i];
        }

        $denominator = sqrt($leftNorm) * sqrt($rightNorm);

        return $denominator < 1e-9 ? 0.0 : $dot / $denominator;
    }
}
