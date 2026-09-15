<?php

namespace App\Services\Embeddings;

/**
 * Deterministic bag-of-tokens embedding used when no remote embedding API is configured.
 * Same text produces the same L2-normalized vector so local RAG still functions.
 */
final class LocalEmbeddingGenerator
{
    /** @var list<string> */
    private const STOPWORDS = [
        'a', 'an', 'the', 'and', 'or', 'but', 'is', 'are', 'was', 'were', 'be', 'been',
        'am', 'do', 'did', 'does', 'of', 'in', 'on', 'at', 'to', 'for', 'from', 'with',
        'as', 'by', 'about', 'into', 'who', 'what', 'where', 'when', 'why', 'how',
        'which', 'this', 'that', 'these', 'those', 'i', 'me', 'my', 'we', 'our', 'you',
        'your', 'he', 'she', 'it', 'they', 'them', 'his', 'her',
    ];

    public function __construct(
        private readonly int $dimensions = 1536,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function tokens(string $text): array
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = str_replace(['-', '_', '/', '\\'], ' ', $normalized);
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $tokens = [];

        foreach ($parts as $part) {
            if (mb_strlen($part) < 2 || in_array($part, self::STOPWORDS, true)) {
                continue;
            }

            $tokens[] = $part;
        }

        return $tokens;
    }

    /**
     * Fraction of query tokens that appear in the document text.
     */
    public static function tokenRecall(string $query, string $document): float
    {
        $queryTokens = array_values(array_unique(self::tokens($query)));

        if ($queryTokens === []) {
            return 0.0;
        }

        $documentTokens = array_flip(self::tokens($document));
        $hits = 0;

        foreach ($queryTokens as $token) {
            if (isset($documentTokens[$token])) {
                $hits++;
            }
        }

        return $hits / count($queryTokens);
    }

    /**
     * @return list<float>
     */
    public function embed(string $text): array
    {
        $vector = array_fill(0, $this->dimensions, 0.0);

        foreach (self::tokens($text) as $token) {
            $hash = hexdec(hash('crc32b', $token));
            $index = $hash % $this->dimensions;
            $vector[$index] += 1.0;

            $secondary = intdiv($hash, $this->dimensions) % $this->dimensions;
            $vector[$secondary] += 0.5;
        }

        return $this->normalize($vector);
    }

    /**
     * @param  list<float>  $vector
     * @return list<float>
     */
    private function normalize(array $vector): array
    {
        $norm = 0.0;

        foreach ($vector as $value) {
            $norm += $value * $value;
        }

        $norm = sqrt($norm);

        if ($norm < 1e-9) {
            $vector[0] = 1.0;

            return $vector;
        }

        foreach ($vector as $i => $value) {
            $vector[$i] = $value / $norm;
        }

        return $vector;
    }
}
