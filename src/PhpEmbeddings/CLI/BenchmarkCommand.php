<?php

declare(strict_types=1);

namespace PhpEmbeddings\CLI;

use PhpEmbeddings\Core\DB;
use PhpEmbeddings\Embedders\BuiltinSmallEmbedder;
use InvalidArgumentException;

/**
 * Benchmark command to test performance
 */
class BenchmarkCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        $options = $this->parseOptions($args);

        if (!isset($options['db'])) {
            $this->printUsage();
            return 1;
        }

        try {
            $this->runBenchmark($options);
            return 0;
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return 1;
        }
    }

    public function getName(): string
    {
        return 'bench';
    }

    public function getDescription(): string
    {
        return 'Run performance benchmarks';
    }

    public function getUsage(): string
    {
        return "php bin/pe bench --db <path> [options]\n" .
               "Options:\n" .
               "  --db <path>            Path to vector database\n" .
               "  --queries <number>     Number of test queries (default: 100)\n" .
               "  --topk <number>        Top-k for each query (default: 10)";
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

    private function runBenchmark(array $options): void
    {
        $dbPath = $options['db'];
        $numQueries = (int) ($options['queries'] ?? 100);
        $topK = (int) ($options['topk'] ?? 10);

        if (!file_exists($dbPath)) {
            throw new \InvalidArgumentException("Database file not found: {$dbPath}");
        }

        // Open database
        $db = $this->openDatabase($dbPath);
        $embedder = new BuiltinSmallEmbedder();

        $documentCount = $db->getDocumentCount();
        echo "Database: {$dbPath}\n";
        echo "Documents: {$documentCount}\n";
        echo "Test queries: {$numQueries}\n";
        echo "Top-K: {$topK}\n\n";

        if ($documentCount === 0) {
            echo "No documents in database. Run 'build' command first.\n";
            $db->close();
            return;
        }

        // Generate test queries
        $testQueries = $this->generateTestQueries($numQueries);

        // Run benchmark
        $this->runSearchBenchmark($db, $embedder, $testQueries, $topK);

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

    private function generateTestQueries(int $count): array
    {
        $queries = [
            "machine learning artificial intelligence",
            "data science analytics statistics",
            "web development programming coding",
            "database management systems",
            "natural language processing",
            "computer vision image recognition",
            "deep learning neural networks",
            "software engineering development",
            "cloud computing infrastructure",
            "cybersecurity information security"
        ];

        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $result[] = $queries[$i % count($queries)];
        }

        return $result;
    }

    private function runSearchBenchmark(DB $db, $embedder, array $queries, int $topK): void
    {
        echo "Running search benchmark...\n";

        $startTime = microtime(true);
        $totalResults = 0;

        foreach ($queries as $i => $query) {
            $queryStart = microtime(true);
            $embedding = $embedder->embed($query);
            $results = $db->search($embedding, $topK);
            $queryTime = microtime(true) - $queryStart;

            $totalResults += count($results);

            if (($i + 1) % 10 === 0) {
                echo "Completed " . ($i + 1) . " queries...\n";
            }
        }

        $totalTime = microtime(true) - $startTime;
        $avgQueryTime = $totalTime / count($queries);
        $queriesPerSecond = count($queries) / $totalTime;

        echo "\nBenchmark Results:\n";
        echo "==================\n";
        echo "Total time: " . round($totalTime, 4) . " seconds\n";
        echo "Average query time: " . round($avgQueryTime * 1000, 2) . " ms\n";
        echo "Queries per second: " . round($queriesPerSecond, 2) . "\n";
        echo "Total results: {$totalResults}\n";
        echo "Average results per query: " . round($totalResults / count($queries), 2) . "\n";
    }

    private function printUsage(): void
    {
        echo $this->getUsage() . "\n";
    }
}
