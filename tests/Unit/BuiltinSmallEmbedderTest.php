<?php

declare(strict_types=1);

namespace PhpEmbeddings\Tests\Unit;

use PhpEmbeddings\Embedders\BuiltinSmallEmbedder;
use PHPUnit\Framework\TestCase;

class BuiltinSmallEmbedderTest extends TestCase
{
    private BuiltinSmallEmbedder $embedder;

    protected function setUp(): void
    {
        $this->embedder = new BuiltinSmallEmbedder();
    }

    public function testEmbed(): void
    {
        $text = "Hello world";
        $embedding = $this->embedder->embed($text);
        
        $this->assertIsArray($embedding);
        $this->assertCount(384, $embedding);
        $this->assertContainsOnly('float', $embedding);
    }

    public function testEmbedBatch(): void
    {
        $texts = ["Hello", "World", "Test"];
        $embeddings = $this->embedder->embedBatch($texts);
        
        $this->assertIsArray($embeddings);
        $this->assertCount(3, $embeddings);
        
        foreach ($embeddings as $embedding) {
            $this->assertIsArray($embedding);
            $this->assertCount(384, $embedding);
        }
    }

    public function testGetDimension(): void
    {
        $dimension = $this->embedder->getDimension();
        $this->assertEquals(384, $dimension);
    }

    public function testGetModelName(): void
    {
        $modelName = $this->embedder->getModelName();
        $this->assertEquals('builtin-small', $modelName);
    }

    public function testEmbeddingConsistency(): void
    {
        $text = "Consistent text";
        $embedding1 = $this->embedder->embed($text);
        $embedding2 = $this->embedder->embed($text);
        
        $this->assertEquals($embedding1, $embedding2);
    }

    public function testEmbeddingNormalization(): void
    {
        $text = "Test normalization";
        $embedding = $this->embedder->embed($text);
        
        // Calculate norm
        $norm = 0.0;
        foreach ($embedding as $value) {
            $norm += $value * $value;
        }
        $norm = sqrt($norm);
        
        // Should be normalized to unit length
        $this->assertEquals(1.0, $norm, '', 0.001);
    }
}
