<?php

namespace App\Services\Documents;

final class DocumentChunker
{
    public function __construct(
        private readonly int $targetTokens = 750,
        private readonly int $overlapTokens = 80,
        private readonly int $minTokens = 40,
    ) {
    }

    /**
     * @return list<array{content: string, token_count: int, index: int}>
     */
    public function chunk(string $text): array
    {
        $paragraphs = preg_split("/\n{2,}/", trim($text)) ?: [];
        $paragraphs = array_values(array_filter(array_map('trim', $paragraphs)));

        if ($paragraphs === []) {
            return [];
        }

        $chunks = [];
        $current = '';

        foreach ($paragraphs as $paragraph) {
            $candidate = $current === '' ? $paragraph : $current."\n\n".$paragraph;

            if ($this->estimateTokens($candidate) <= $this->targetTokens) {
                $current = $candidate;
                continue;
            }

            if ($this->estimateTokens($paragraph) > $this->targetTokens) {
                if ($current !== '') {
                    $chunks[] = $current;
                    $current = $this->overlapFrom($current);
                }

                foreach ($this->splitLongParagraph($paragraph) as $piece) {
                    $chunks[] = $piece;
                    $current = $this->overlapFrom($piece);
                }

                continue;
            }

            if ($current !== '') {
                $chunks[] = $current;
            }

            $overlap = $this->overlapFrom($current);
            $current = $overlap === '' ? $paragraph : $overlap."\n\n".$paragraph;
        }

        if ($current !== '' && $this->estimateTokens($current) >= $this->minTokens) {
            $chunks[] = $current;
        } elseif ($current !== '' && $chunks === []) {
            $chunks[] = $current;
        }

        $payload = [];

        foreach (array_values($chunks) as $index => $content) {
            $payload[] = [
                'content' => $content,
                'token_count' => $this->estimateTokens($content),
                'index' => $index,
            ];
        }

        return $payload;
    }

    public function estimateTokens(string $text): int
    {
        return max(1, (int) round(strlen($text) / 4));
    }

    /**
     * @return list<string>
     */
    private function splitLongParagraph(string $paragraph): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $paragraph) ?: [$paragraph];
        $pieces = [];
        $current = '';

        foreach ($sentences as $sentence) {
            $candidate = $current === '' ? $sentence : $current.' '.$sentence;

            if ($this->estimateTokens($candidate) <= $this->targetTokens) {
                $current = $candidate;
                continue;
            }

            if ($current !== '') {
                $pieces[] = $current;
            }

            $current = $sentence;
        }

        if ($current !== '') {
            $pieces[] = $current;
        }

        return $pieces;
    }

    private function overlapFrom(string $text): string
    {
        if ($this->overlapTokens <= 0 || $text === '') {
            return '';
        }

        $words = preg_split('/\s+/', $text) ?: [];
        $slice = array_slice($words, -max(1, $this->overlapTokens));

        return implode(' ', $slice);
    }
}
