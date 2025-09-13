# Development Guide

## Setup

1. Clone the repository:
```bash
git clone https://github.com/makalin/php-embeddings.git
cd php-embeddings
```

2. Install dependencies:
```bash
composer install
```

3. Make the CLI executable:
```bash
chmod +x bin/pe
```

## Development Commands

### Testing
```bash
# Run all tests
make test

# Run tests with coverage
make test-coverage

# Run specific test
vendor/bin/phpunit tests/Unit/VectorMathTest.php
```

### Code Quality
```bash
# Run linting
make lint

# Fix linting issues
make lint-fix

# Run static analysis
make stan

# Fix code style
make cs-fix

# Run all quality checks
make qa
```

### Demo
```bash
# Run demo with sample data
make demo
```

## Project Structure

```
php-embeddings/
├── bin/                    # CLI entry point
│   └── pe
├── src/                    # Source code
│   └── PhpEmbeddings/
│       ├── Core/           # Core classes
│       │   └── DB.php
│       ├── Embedders/      # Embedding models
│       │   ├── EmbedderInterface.php
│       │   └── BuiltinSmallEmbedder.php
│       ├── Storage/        # Storage backends
│       │   ├── StorageInterface.php
│       │   ├── SQLiteStorage.php
│       │   └── JSONLStorage.php
│       ├── Math/           # Vector math operations
│       │   └── VectorMath.php
│       └── CLI/            # CLI commands
│           ├── CommandInterface.php
│           ├── Application.php
│           ├── BuildCommand.php
│           ├── QueryCommand.php
│           └── ExportCommand.php
├── tests/                  # Test files
│   ├── Unit/              # Unit tests
│   └── Integration/       # Integration tests
├── data/                  # Sample data
│   ├── sample.csv
│   └── test/
├── docs/                  # Documentation
├── composer.json          # Dependencies
├── Makefile              # Build commands
├── phpunit.xml           # Test configuration
├── phpstan.neon          # Static analysis config
├── .php-cs-fixer.php     # Code style config
└── .phpcs.xml            # Linting config
```

## Adding New Features

### 1. New Embedder

Create a new embedder class implementing `EmbedderInterface`:

```php
<?php

namespace PhpEmbeddings\Embedders;

class MyEmbedder implements EmbedderInterface
{
    public function embed(string $text): array
    {
        // Implementation
    }

    public function embedBatch(array $texts): array
    {
        // Implementation
    }

    public function getDimension(): int
    {
        return 512; // Your dimension
    }

    public function getModelName(): string
    {
        return 'my-model';
    }
}
```

### 2. New Storage Backend

Create a new storage class implementing `StorageInterface`:

```php
<?php

namespace PhpEmbeddings\Storage;

class MyStorage implements StorageInterface
{
    // Implement all interface methods
}
```

### 3. New CLI Command

Create a new command class implementing `CommandInterface`:

```php
<?php

namespace PhpEmbeddings\CLI;

class MyCommand implements CommandInterface
{
    public function execute(array $args): int
    {
        // Implementation
    }

    public function getName(): string
    {
        return 'my-command';
    }

    public function getDescription(): string
    {
        return 'My command description';
    }

    public function getUsage(): string
    {
        return 'Usage information';
    }
}
```

Then register it in `Application.php`:

```php
private function registerCommands(): void
{
    $this->commands = [
        'build' => new BuildCommand(),
        'query' => new QueryCommand(),
        'export' => new ExportCommand(),
        'my-command' => new MyCommand(), // Add here
    ];
}
```

## Testing Guidelines

### Unit Tests
- Test individual methods and classes
- Use mocks for dependencies
- Aim for high coverage
- Test edge cases and error conditions

### Integration Tests
- Test complete workflows
- Use real database files
- Test CLI commands end-to-end
- Clean up test data

### Test Data
- Use the `data/test/` directory for test files
- Create minimal test datasets
- Use descriptive test data

## Code Style

The project follows PSR-12 coding standards with some additional rules:

- Use strict types (`declare(strict_types=1)`)
- Use type hints for all parameters and return types
- Use meaningful variable and method names
- Add docblocks for public methods
- Keep methods focused and small
- Use dependency injection where possible

## Performance Considerations

### Vector Operations
- Use SIMD-style optimizations in `VectorMath`
- Process vectors in batches when possible
- Avoid unnecessary array copies
- Use packed binary storage for vectors

### Database Operations
- Use transactions for batch operations
- Create indexes for frequently queried fields
- Use prepared statements
- Consider memory usage for large datasets

### Memory Management
- Process large CSVs in batches
- Use streaming for large files
- Clean up resources properly
- Consider memory limits for large datasets

## Debugging

### Enable Debug Mode
```bash
php -d display_errors=1 -d error_reporting=E_ALL bin/pe build --csv data.csv --id-col id --text-col text
```

### Common Issues

1. **Memory issues with large CSVs**
   - Use `--batch` parameter
   - Increase memory limit: `php -d memory_limit=2G`

2. **Slow queries**
   - Create indexes: `$db->createIndexes()`
   - Use SQLite instead of JSONL for large datasets
   - Normalize embeddings: `--normalize`

3. **CSV parsing issues**
   - Check file encoding (should be UTF-8)
   - Verify column names match exactly
   - Handle empty or malformed rows

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Run quality checks: `make qa`
6. Submit a pull request

## Release Process

1. Update version in `composer.json`
2. Update `CHANGELOG.md`
3. Run full test suite: `make qa`
4. Create git tag
5. Push to repository
