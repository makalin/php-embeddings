<?php

declare(strict_types=1);

namespace PhpEmbeddings\CLI;

use PhpEmbeddings\Core\DB;
use InvalidArgumentException;

/**
 * Export command to convert between formats
 */
class ExportCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $options = $this->parseOptions($args);

        if (!isset($options['db']) || !isset($options['out'])) {
            $this->printUsage();
            return 1;
        }

        try {
            $this->exportDatabase($options);
            echo "Export completed successfully!\n";
            return 0;
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return 1;
        }
    }

    public function getName(): string
    {
        return 'export';
    }

    public function getDescription(): string
    {
        return 'Export vector database to different formats';
    }

    public function getUsage(): string
    {
        return "php bin/pe export --db <path> --out <path> [options]\n" .
               "Options:\n" .
               "  --db <path>            Path to input database\n" .
               "  --out <path>           Path to output file\n" .
               "  --format <format>      Output format: jsonl|csv (default: jsonl)";
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

    private function exportDatabase(array $options): void
    {
        $dbPath = $options['db'];
        $outputPath = $options['out'];
        $format = $options['format'] ?? 'jsonl';

        if (!file_exists($dbPath)) {
            throw new \InvalidArgumentException("Database file not found: {$dbPath}");
        }

        // Open database
        $db = $this->openDatabase($dbPath);

        // Export based on format
        if ($format === 'csv') {
            $db->exportToCsv($outputPath);
        } else {
            $db->exportToJsonl($outputPath);
        }

        $db->close();
    }

    private function openDatabase(string $path): DB
    {
        if (str_ends_with($path, '.jsonl')) {
            return DB::openJsonl($path);
        } else {
            return DB::open($path);
        }
    }

    private function printUsage(): void
    {
        echo $this->getUsage() . "\n";
    }
}
