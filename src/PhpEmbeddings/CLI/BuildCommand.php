<?php

declare(strict_types=1);

namespace PhpEmbeddings\CLI;

use PhpEmbeddings\Core\DB;
use PhpEmbeddings\Embedders\BuiltinSmallEmbedder;
use PhpEmbeddings\Embedders\EmbedderInterface;
use InvalidArgumentException;

/**
 * Build command to convert CSV to vector database
 */
class BuildCommand implements CommandInterface
{
    private EmbedderInterface $embedder;

    public function __construct()
    {
        $this->embedder = new BuiltinSmallEmbedder();
    }

    public function execute(array $args): int
    {
        $options = $this->parseOptions($args);

        if (!isset($options['csv']) || !isset($options['id-col']) || !isset($options['text-col'])) {
            $this->printUsage();
            return 1;
        }

        try {
            $this->buildDatabase($options);
            echo "Database built successfully!\n";
            return 0;
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return 1;
        }
    }

    public function getName(): string
    {
        return 'build';
    }

    public function getDescription(): string
    {
        return 'Build vector database from CSV file';
    }

    public function getUsage(): string
    {
        return "php bin/pe build --csv <path> --id-col <name> --text-col <name> [options]\n" .
               "Options:\n" .
               "  --csv <path>           Path to CSV file\n" .
               "  --id-col <name>        Column name for document ID\n" .
               "  --text-col <name>      Column name for text content\n" .
               "  --meta-cols <cols>     Comma-separated metadata columns\n" .
               "  --out <path>           Output file path (default: vectors.sqlite)\n" .
               "  --format <format>      Output format: sqlite|jsonl (default: sqlite)\n" .
               "  --dim <dimension>      Embedding dimension (default: 384)\n" .
               "  --model <model>        Embedding model (default: builtin-small)\n" .
               "  --batch <size>         Batch size for processing (default: 1024)\n" .
               "  --normalize            Normalize embeddings\n" .
               "  --append               Append to existing database\n" .
               "  --no-index             Skip creating indexes";
    }

    private function parseOptions(array $args): array
    {
        $options = [];
        $i = 0;

        while ($i < count($args)) {
            $arg = $args[$i];

            if (str_starts_with($arg, '--')) {
                $key = substr($arg, 2);
                $value = $args[$i + 1] ?? null;
                $options[$key] = $value;
                $i += 2;
            } else {
                $i++;
            }
        }

        return $options;
    }

    private function buildDatabase(array $options): void
    {
        $csvPath = $options['csv'];
        $idCol = $options['id-col'];
        $textCol = $options['text-col'];
        $metaCols = isset($options['meta-cols']) ? explode(',', $options['meta-cols']) : [];
        $outputPath = $options['out'] ?? 'vectors.sqlite';
        $format = $options['format'] ?? 'sqlite';
        $batchSize = (int) ($options['batch'] ?? 1024);
        $normalize = isset($options['normalize']);
        $append = isset($options['append']);
        $createIndex = !isset($options['no-index']);

        if (!file_exists($csvPath)) {
            throw new \InvalidArgumentException("CSV file not found: {$csvPath}");
        }

        // Determine storage backend
        $db = $this->createDatabase($outputPath, $format, $append);

        // Process CSV
        $this->processCsv($db, $csvPath, $idCol, $textCol, $metaCols, $batchSize, $normalize);

        // Create indexes if requested
        if ($createIndex) {
            $db->createIndexes();
        }

        $db->close();
    }

    private function createDatabase(string $outputPath, string $format, bool $append): DB
    {
        if ($format === 'jsonl') {
            return DB::openJsonl($outputPath);
        } else {
            return DB::open($outputPath);
        }
    }

    private function processCsv(DB $db, string $csvPath, string $idCol, string $textCol, array $metaCols, int $batchSize, bool $normalize): void
    {
        $file = fopen($csvPath, 'r');
        if (!$file) {
            throw new \RuntimeException("Failed to open CSV file: {$csvPath}");
        }

        // Read header
        $header = fgetcsv($file);
        if (!$header) {
            throw new \RuntimeException("Failed to read CSV header");
        }

        // Find column indices
        $idIndex = array_search($idCol, $header);
        $textIndex = array_search($textCol, $header);
        
        if ($idIndex === false) {
            throw new \InvalidArgumentException("ID column '{$idCol}' not found in CSV");
        }
        if ($textIndex === false) {
            throw new \InvalidArgumentException("Text column '{$textCol}' not found in CSV");
        }

        $metaIndices = [];
        foreach ($metaCols as $col) {
            $index = array_search($col, $header);
            if ($index !== false) {
                $metaIndices[$col] = $index;
            }
        }

        $batch = [];
        $processed = 0;

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) !== count($header)) {
                continue; // Skip malformed rows
            }

            $id = $row[$idIndex];
            $text = $row[$textIndex];
            
            // Extract metadata
            $metadata = [];
            foreach ($metaIndices as $col => $index) {
                $metadata[$col] = $row[$index];
            }

            // Generate embedding
            $embedding = $this->embedder->embed($text);
            
            if ($normalize) {
                $embedding = $this->normalizeVector($embedding);
            }

            $batch[] = [
                'id' => $id,
                'text' => $text,
                'embedding' => $embedding,
                'metadata' => $metadata
            ];

            if (count($batch) >= $batchSize) {
                $this->processBatch($db, $batch);
                $processed += count($batch);
                echo "Processed {$processed} documents...\n";
                $batch = [];
            }
        }

        // Process remaining batch
        if (!empty($batch)) {
            $this->processBatch($db, $batch);
            $processed += count($batch);
        }

        fclose($file);
        echo "Total processed: {$processed} documents\n";
    }

    private function processBatch(DB $db, array $batch): void
    {
        foreach ($batch as $doc) {
            $db->addDocument(
                $doc['id'],
                $doc['text'],
                $doc['embedding'],
                $doc['metadata']
            );
        }
    }

    private function normalizeVector(array $vector): array
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

    private function printUsage(): void
    {
        echo $this->getUsage() . "\n";
    }
}
