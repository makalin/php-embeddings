<?php

declare(strict_types=1);

namespace PhpEmbeddings\Core;

use PhpEmbeddings\Storage\SQLiteStorage;
use PhpEmbeddings\Storage\JSONLStorage;
use PhpEmbeddings\Storage\StorageInterface;
use PhpEmbeddings\Math\VectorMath;
use InvalidArgumentException;

/**
 * Main database class for vector operations
 */
class DB
{
    private StorageInterface $storage;
    private VectorMath $vectorMath;

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
        $this->vectorMath = new VectorMath();
    }

    /**
     * Open SQLite database
     */
    public static function open(string $path): self
    {
        return new self(new SQLiteStorage($path));
    }

    /**
     * Open JSONL database
     */
    public static function openJsonl(string $path): self
    {
        return new self(new JSONLStorage($path));
    }

    /**
     * Add document with embedding
     */
    public function addDocument(string $id, string $text, array $embedding, array $metadata = []): void
    {
        $this->storage->addDocument($id, $text, $embedding, $metadata);
    }

    /**
     * Search for similar documents
     */
    public function search(string $query, int $topK = 10, array $filter = []): array
    {
        $queryEmbedding = $this->getQueryEmbedding($query);
        return $this->storage->search($queryEmbedding, $topK, $filter);
    }

    /**
     * Get query embedding (placeholder - would use actual embedder)
     */
    private function getQueryEmbedding(string $query): array
    {
        // This would normally use an embedder
        // For now, return a dummy embedding
        return array_fill(0, 384, 0.0);
    }

    /**
     * Get document by ID
     */
    public function getDocument(string $id): ?array
    {
        return $this->storage->getDocument($id);
    }

    /**
     * Get all documents
     */
    public function getAllDocuments(): array
    {
        return $this->storage->getAllDocuments();
    }

    /**
     * Get document count
     */
    public function getDocumentCount(): int
    {
        return $this->storage->getDocumentCount();
    }

    /**
     * Close database connection
     */
    public function close(): void
    {
        $this->storage->close();
    }

    /**
     * Create indexes for better performance
     */
    public function createIndexes(): void
    {
        $this->storage->createIndexes();
    }

    /**
     * Export to JSONL format
     */
    public function exportToJsonl(string $outputPath): void
    {
        $this->storage->exportToJsonl($outputPath);
    }

    /**
     * Export to CSV format
     */
    public function exportToCsv(string $outputPath): void
    {
        $this->storage->exportToCsv($outputPath);
    }
}
