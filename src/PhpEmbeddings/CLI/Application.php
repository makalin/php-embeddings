<?php

declare(strict_types=1);

namespace PhpEmbeddings\CLI;

use PhpEmbeddings\CLI\BuildCommand;
use PhpEmbeddings\CLI\QueryCommand;
use PhpEmbeddings\CLI\ExportCommand;
use PhpEmbeddings\CLI\BenchmarkCommand;
use InvalidArgumentException;

/**
 * Main CLI application
 */
class Application
{
    private array $commands = [];

    public function __construct()
    {
        $this->registerCommands();
    }

    private function registerCommands(): void
    {
        $this->commands = [
            'build' => new BuildCommand(),
            'query' => new QueryCommand(),
            'export' => new ExportCommand(),
            'bench' => new BenchmarkCommand(),
        ];
    }

    public function run(array $argv): int
    {
        if (count($argv) < 2) {
            $this->printHelp();
            return 1;
        }

        $commandName = $argv[1];
        $args = array_slice($argv, 2);

        if ($commandName === '--help' || $commandName === '-h') {
            $this->printHelp();
            return 0;
        }

        if (!isset($this->commands[$commandName])) {
            echo "Unknown command: {$commandName}\n\n";
            $this->printHelp();
            return 1;
        }

        $command = $this->commands[$commandName];
        return $command->execute($args);
    }

    private function printHelp(): void
    {
        echo "php-embeddings - CLI to convert CSV → OpenAI-compatible vector DB\n\n";
        echo "Usage: php bin/pe <command> [options]\n\n";
        echo "Commands:\n";

        foreach ($this->commands as $name => $command) {
            echo "  {$name:<12} {$command->getDescription()}\n";
        }

        echo "\nFor help with a specific command, run:\n";
        echo "  php bin/pe <command> --help\n\n";
        echo "Examples:\n";
        echo "  php bin/pe build --csv data.csv --id-col id --text-col text\n";
        echo "  php bin/pe query --db vectors.sqlite --q 'search query'\n";
        echo "  php bin/pe export --db vectors.sqlite --out vectors.jsonl\n";
        echo "  php bin/pe bench --db vectors.sqlite\n";
    }
}
