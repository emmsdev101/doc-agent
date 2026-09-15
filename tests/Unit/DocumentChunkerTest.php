<?php

namespace Tests\Unit;

use App\Services\Documents\DocumentChunker;
use PHPUnit\Framework\TestCase;

class DocumentChunkerTest extends TestCase
{
    public function test_it_splits_long_text_into_overlapping_chunks(): void
    {
        $chunker = new DocumentChunker(targetTokens: 40, overlapTokens: 8, minTokens: 5);
        $paragraphs = [];

        for ($i = 1; $i <= 12; $i++) {
            $paragraphs[] = 'Paragraph '.$i.' contains enough words to exceed a tiny token budget for testing purposes.';
        }

        $chunks = $chunker->chunk(implode("\n\n", $paragraphs));

        $this->assertGreaterThan(1, count($chunks));
        $this->assertSame(0, $chunks[0]['index']);
        $this->assertNotSame('', $chunks[0]['content']);
    }
}
