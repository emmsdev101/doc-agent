<?php

namespace Tests\Unit;

use App\Services\Embeddings\LocalEmbeddingGenerator;
use PHPUnit\Framework\TestCase;

class LocalEmbeddingGeneratorTest extends TestCase
{
    public function test_it_returns_normalized_vectors_of_the_configured_size(): void
    {
        $generator = new LocalEmbeddingGenerator(32);
        $vector = $generator->embed('refund policy for annual subscriptions');

        $this->assertCount(32, $vector);

        $norm = 0.0;
        foreach ($vector as $value) {
            $norm += $value * $value;
        }

        $this->assertEqualsWithDelta(1.0, sqrt($norm), 0.0001);
        $this->assertSame($vector, $generator->embed('refund policy for annual subscriptions'));
    }
}
