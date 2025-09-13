<?php

declare(strict_types=1);

namespace PhpEmbeddings\Embedders;

/**
 * Interface for text embedders
 */
interface EmbedderInterface
{
    /**
     * Generate embedding for given text
     */
    public function embed(string $text): array;

    /**
     * Generate embeddings for multiple texts
     */
    public function embedBatch(array $texts): array;

    /**
     * Get embedding dimension
     */
    public function getDimension(): int;

    /**
     * Get model name
     */
    public function getModelName(): string;
}
