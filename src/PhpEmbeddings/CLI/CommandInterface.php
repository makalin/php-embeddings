<?php

declare(strict_types=1);

namespace PhpEmbeddings\CLI;

/**
 * Interface for CLI commands
 */
interface CommandInterface
{
    /**
     * Execute the command
     */
    public function execute(array $args): int;

    /**
     * Get command name
     */
    public function getName(): string;

    /**
     * Get command description
     */
    public function getDescription(): string;

    /**
     * Get command usage
     */
    public function getUsage(): string;
}
