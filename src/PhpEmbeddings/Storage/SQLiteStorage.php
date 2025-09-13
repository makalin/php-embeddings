<?php

declare(strict_types=1);

namespace PhpEmbeddings\Storage;

use PDO;
use PDOException;
use PhpEmbeddings\Math\VectorMath;

/**
 * SQLite storage backend for vectors
 */
class SQLiteStorage implements StorageInterface
{
    private PDO $pdo;
    private VectorMath $vectorMath;
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->vectorMath = new VectorMath();
        $this->initializeDatabase();
    }

    /**
     * Initialize SQLite database
     */
    private function initializeDatabase(): void
    {
        try {
            $this->pdo = new PDO("sqlite:{$this->path}");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->createTables();
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to initialize SQLite database: " . $e->getMessage());
        }
    }

    /**
     * Create database tables
     */
    private function createTables(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS documents (
                id TEXT PRIMARY KEY,
                text TEXT NOT NULL,
                metadata JSON
            );
            
            CREATE TABLE IF NOT EXISTS embeddings (
                id TEXT PRIMARY KEY,
                dim INTEGER NOT NULL,
                vec BLOB NOT NULL,
                FOREIGN KEY (id) REFERENCES documents(id) ON DELETE CASCADE
            );
        ";
        
        $this->pdo->exec($sql);
    }

    /**
     * Add document with embedding
     */
    public function addDocument(string $id, string $text, array $embedding, array $metadata = []): void
    {
        try {
            $this->pdo->beginTransaction();

            // Insert document
            $stmt = $this->pdo->prepare("
                INSERT OR REPLACE INTO documents (id, text, metadata) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$id, $text, json_encode($metadata)]);

            // Insert embedding
            $packedVector = $this->vectorMath->packVector($embedding);
            $stmt = $this->pdo->prepare("
                INSERT OR REPLACE INTO embeddings (id, dim, vec) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$id, count($embedding), $packedVector]);

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new \RuntimeException("Failed to add document: " . $e->getMessage());
        }
    }

    /**
     * Search for similar documents
     */
    public function search(array $queryEmbedding, int $topK = 10, array $filter = []): array
    {
        $results = [];
        
        // Get all documents with their embeddings
        $sql = "SELECT d.id, d.text, d.metadata, e.vec, e.dim FROM documents d 
                JOIN embeddings e ON d.id = e.id";
        
        $params = [];
        if (!empty($filter)) {
            $conditions = [];
            foreach ($filter as $key => $value) {
                $conditions[] = "json_extract(d.metadata, '$.{$key}') = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $embedding = $this->vectorMath->unpackVector($row['vec'], $row['dim']);
            $similarity = $this->vectorMath->cosineSimilarity($queryEmbedding, $embedding);
            
            $results[] = [
                'id' => $row['id'],
                'text' => $row['text'],
                'metadata' => json_decode($row['metadata'], true) ?: [],
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
        $stmt = $this->pdo->prepare("
            SELECT d.id, d.text, d.metadata, e.vec, e.dim 
            FROM documents d 
            JOIN embeddings e ON d.id = e.id 
            WHERE d.id = ?
        ");
        $stmt->execute([$id]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return [
            'id' => $row['id'],
            'text' => $row['text'],
            'metadata' => json_decode($row['metadata'], true) ?: [],
            'embedding' => $this->vectorMath->unpackVector($row['vec'], $row['dim'])
        ];
    }

    /**
     * Get all documents
     */
    public function getAllDocuments(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT d.id, d.text, d.metadata, e.vec, e.dim 
            FROM documents d 
            JOIN embeddings e ON d.id = e.id
        ");
        $stmt->execute();
        
        $documents = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $documents[] = [
                'id' => $row['id'],
                'text' => $row['text'],
                'metadata' => json_decode($row['metadata'], true) ?: [],
                'embedding' => $this->vectorMath->unpackVector($row['vec'], $row['dim'])
            ];
        }

        return $documents;
    }

    /**
     * Get document count
     */
    public function getDocumentCount(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM documents");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Close database connection
     */
    public function close(): void
    {
        $this->pdo = null;
    }

    /**
     * Create indexes for better performance
     */
    public function createIndexes(): void
    {
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_docs_meta ON documents(json_extract(metadata, '$.category'))",
            "CREATE INDEX IF NOT EXISTS idx_docs_text ON documents(text)",
        ];

        foreach ($indexes as $sql) {
            $this->pdo->exec($sql);
        }
    }

    /**
     * Export to JSONL format
     */
    public function exportToJsonl(string $outputPath): void
    {
        $documents = $this->getAllDocuments();
        $file = fopen($outputPath, 'w');
        
        if (!$file) {
            throw new \RuntimeException("Failed to open output file: {$outputPath}");
        }

        foreach ($documents as $doc) {
            $line = json_encode([
                'id' => $doc['id'],
                'text' => $doc['text'],
                'embedding' => $doc['embedding'],
                'metadata' => $doc['metadata']
            ]);
            fwrite($file, $line . "\n");
        }

        fclose($file);
    }

    /**
     * Export to CSV format
     */
    public function exportToCsv(string $outputPath): void
    {
        $documents = $this->getAllDocuments();
        $file = fopen($outputPath, 'w');
        
        if (!$file) {
            throw new \RuntimeException("Failed to open output file: {$outputPath}");
        }

        // Write header
        fputcsv($file, ['id', 'text', 'embedding', 'metadata']);

        foreach ($documents as $doc) {
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
