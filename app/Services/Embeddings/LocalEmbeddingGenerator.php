<?php

namespace App\Services\Embeddings;

/**
 * Deterministic bag-of-tokens embedding used when no remote embedding API is configured.
 * Same text produces the same  L2-normalized vector so local RAG still functions.
 */
final class LocalEmbeddingGenerator
{
    public function __construct(
        private readonly int $dimensions = 1536,
    ) {
    }

    /**
     * @return list<float>
     */
    public function embed(string $text): array
    {
        $vector = array_fill(0, $this->dimensions, 0.0);
        $tokens = preg_split('/\s+/u', mb_strtolower(trim($text))) ?: [];

        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }

            $hash = crc32($token);
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
