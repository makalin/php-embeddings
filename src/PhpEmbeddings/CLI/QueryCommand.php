<?php

declare(strict_types=1);

namespace PhpEmbeddings\CLI;

use PhpEmbeddings\Core\DB;
use PhpEmbeddings\Embedders\BuiltinSmallEmbedder;
use PhpEmbeddings\Embedders\EmbedderInterface;
use InvalidArgumentException;

/**
 * Query command to search vector database
 */
class QueryCommand implements CommandInterface
{
    private EmbedderInterface $embedder;

    public function __construct()
    {
        $this->embedder = new BuiltinSmallEmbedder();
    }

    public function execute(array $args): int
    {
        $options = $this->parseOptions($args);

        if (!isset($options['db']) || !isset($options['q'])) {
            $this->printUsage();
            return 1;
        }

        try {
            $this->queryDatabase($options);
            return 0;
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return 1;
        }
    }

    public function getName(): string
    {
        return 'query';
    }

    public function getDescription(): string
    {
        return 'Query vector database for similar documents';
    }

    public function getUsage(): string
    {
        return "php bin/pe query --db <path> --q <query> [options]\n" .
               "Options:\n" .
               "  --db <path>            Path to vector database\n" .
               "  --q <query>            Search query text\n" .
               "  --topk <number>        Number of results to return (default: 10)\n" .
               "  --filter <filter>      Filter by metadata (e.g., 'category=docs,lang=en')";
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

    private function queryDatabase(array $options): void
    {
        $dbPath = $options['db'];
        $query = $options['q'];
        $topK = (int) ($options['topk'] ?? 10);
        $filter = $this->parseFilter($options['filter'] ?? '');

        if (!file_exists($dbPath)) {
            throw new \InvalidArgumentException("Database file not found: {$dbPath}");
        }

        // Determine database type and open
        $db = $this->openDatabase($dbPath);

        // Generate query embedding
        $queryEmbedding = $this->embedder->embed($query);

        // Search
        $results = $db->search($queryEmbedding, $topK, $filter);

        // Output results
        $this->outputResults($query, $results);

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

    private function parseFilter(string $filterString): array
    {
        if (empty($filterString)) {
            return [];
        }

        $filter = [];
        $pairs = explode(',', $filterString);

        foreach ($pairs as $pair) {
            $parts = explode('=', $pair, 2);
            if (count($parts) === 2) {
                $filter[trim($parts[0])] = trim($parts[1]);
            }
        }

        return $filter;
    }

    private function outputResults(string $query, array $results): void
    {
        $output = [
            'query' => $query,
            'results' => []
        ];

        foreach ($results as $result) {
            $output['results'][] = [
                'id' => $result['id'],
                'score' => round($result['score'], 4),
                'text' => $result['text'],
                'metadata' => $result['metadata']
            ];
        }

        echo json_encode($output, JSON_PRETTY_PRINT) . "\n";
    }

    private function printUsage(): void
    {
        echo $this->getUsage() . "\n";
    }
}
