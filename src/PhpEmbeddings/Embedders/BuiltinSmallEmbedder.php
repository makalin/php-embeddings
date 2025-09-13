<?php

declare(strict_types=1);

namespace PhpEmbeddings\Embedders;

/**
 * Built-in small embedder for demonstration purposes
 * This is a placeholder implementation - in reality you'd use a proper model
 */
class BuiltinSmallEmbedder implements EmbedderInterface
{
    private const DIMENSION = 384;
    private const MODEL_NAME = 'builtin-small';

    /**
     * Generate embedding for given text
     */
    public function embed(string $text): array
    {
        // This is a placeholder implementation
        // In reality, you'd use a proper embedding model
        $hash = crc32($text);
        $embedding = [];
        
        for ($i = 0; $i < self::DIMENSION; $i++) {
            $embedding[] = sin($hash + $i) * 0.1;
        }

        return $this->normalize($embedding);
    }

    /**
     * Generate embeddings for multiple texts
     */
    public function embedBatch(array $texts): array
    {
        $embeddings = [];
        foreach ($texts as $text) {
            $embeddings[] = $this->embed($text);
        }
        return $embeddings;
    }

    /**
     * Get embedding dimension
     */
    public function getDimension(): int
    {
        return self::DIMENSION;
    }

    /**
     * Get model name
     */
    public function getModelName(): string
    {
        return self::MODEL_NAME;
    }

    /**
     * Normalize vector to unit length
     */
    private function normalize(array $vector): array
    {
        $norm = 0.0;
        foreach ($vector as $value) {
            $norm += $value * $value;
        }
        $norm = sqrt($norm);

        if ($norm === 0.0) {
            return $vector;
        }

        return array_map(fn($value) => $value / $norm, $vector);
    }
}
