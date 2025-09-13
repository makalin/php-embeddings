<?php

declare(strict_types=1);

namespace PhpEmbeddings\Storage;

/**
 * Interface for vector storage backends
 */
interface StorageInterface
{
    /**
     * Add document with embedding
     */
    public function addDocument(string $id, string $text, array $embedding, array $metadata = []): void;

    /**
     * Search for similar documents
     */
    public function search(array $queryEmbedding, int $topK = 10, array $filter = []): array;

    /**
     * Get document by ID
     */
    public function getDocument(string $id): ?array;

    /**
     * Get all documents
     */
    public function getAllDocuments(): array;

    /**
     * Get document count
     */
    public function getDocumentCount(): int;

    /**
     * Close database connection
     */
    public function close(): void;

    /**
     * Create indexes for better performance
     */
    public function createIndexes(): void;

    /**
     * Export to JSONL format
     */
    public function exportToJsonl(string $outputPath): void;

    /**
     * Export to CSV format
     */
    public function exportToCsv(string $outputPath): void;
}
