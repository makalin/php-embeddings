<?php

declare(strict_types=1);

namespace PhpEmbeddings\Storage;

use PhpEmbeddings\Math\VectorMath;
use InvalidArgumentException;

/**
 * JSONL storage backend for vectors
 */
class JSONLStorage implements StorageInterface
{
    private string $path;
    private VectorMath $vectorMath;
    private array $documents = [];

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->vectorMath = new VectorMath();
        $this->loadDocuments();
    }

    /**
     * Load documents from JSONL file
     */
    private function loadDocuments(): void
    {
        if (!file_exists($this->path)) {
            return;
        }

        $file = fopen($this->path, 'r');
        if (!$file) {
            throw new \RuntimeException("Failed to open JSONL file: {$this->path}");
        }

        while (($line = fgets($file)) !== false) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $data = json_decode($line, true);
            if ($data === null) {
                continue;
            }

            $this->documents[$data['id']] = $data;
        }

        fclose($file);
    }

    /**
     * Save documents to JSONL file
     */
    private function saveDocuments(): void
    {
        $file = fopen($this->path, 'w');
        if (!$file) {
            throw new \RuntimeException("Failed to open JSONL file for writing: {$this->path}");
        }

        foreach ($this->documents as $doc) {
            fwrite($file, json_encode($doc) . "\n");
        }

        fclose($file);
    }

    /**
     * Add document with embedding
     */
    public function addDocument(string $id, string $text, array $embedding, array $metadata = []): void
    {
        $this->documents[$id] = [
            'id' => $id,
            'text' => $text,
            'embedding' => $embedding,
            'metadata' => $metadata
        ];
        
        $this->saveDocuments();
    }

    /**
     * Search for similar documents
     */
    public function search(array $queryEmbedding, int $topK = 10, array $filter = []): array
    {
        $results = [];

        foreach ($this->documents as $doc) {
            // Apply filters
            if (!empty($filter)) {
                $matches = true;
                foreach ($filter as $key => $value) {
                    if (!isset($doc['metadata'][$key]) || $doc['metadata'][$key] !== $value) {
                        $matches = false;
                        break;
                    }
                }
                if (!$matches) {
                    continue;
                }
            }

            $similarity = $this->vectorMath->cosineSimilarity($queryEmbedding, $doc['embedding']);
            
            $results[] = [
                'id' => $doc['id'],
                'text' => $doc['text'],
                'metadata' => $doc['metadata'],
                'score' => $similarity
            ];
        }

        // Sort by similarity score (descending)
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($results, 0, $topK);
    }

    /**
     * Get document by ID
     */
    public function getDocument(string $id): ?array
    {
        return $this->documents[$id] ?? null;
    }

    /**
     * Get all documents
     */
    public function getAllDocuments(): array
    {
        return array_values($this->documents);
    }

    /**
     * Get document count
     */
    public function getDocumentCount(): int
    {
        return count($this->documents);
    }

    /**
     * Close database connection
     */
    public function close(): void
    {
        // JSONL doesn't need explicit closing
    }

    /**
     * Create indexes for better performance
     */
    public function createIndexes(): void
    {
        // JSONL doesn't support indexes
    }

    /**
     * Export to JSONL format
     */
    public function exportToJsonl(string $outputPath): void
    {
        if ($outputPath === $this->path) {
            return; // Already in JSONL format
        }

        $file = fopen($outputPath, 'w');
        if (!$file) {
            throw new \RuntimeException("Failed to open output file: {$outputPath}");
        }

        foreach ($this->documents as $doc) {
            fwrite($file, json_encode($doc) . "\n");
        }

        fclose($file);
    }

    /**
     * Export to CSV format
     */
    public function exportToCsv(string $outputPath): void
    {
        $file = fopen($outputPath, 'w');
        if (!$file) {
            throw new \RuntimeException("Failed to open output file: {$outputPath}");
        }

        // Write header
        fputcsv($file, ['id', 'text', 'embedding', 'metadata']);

        foreach ($this->documents as $doc) {
            fputcsv($file, [
                $doc['id'],
                $doc['text'],
                json_encode($doc['embedding']),
                json_encode($doc['metadata'])
            ]);
        }

        fclose($file);
    }
}
