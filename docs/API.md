# API Documentation

## Core Classes

### DB

The main database class for vector operations.

#### Methods

##### `static open(string $path): self`
Open SQLite database.

**Parameters:**
- `$path` (string): Path to SQLite database file

**Returns:** `self` - DB instance

##### `static openJsonl(string $path): self`
Open JSONL database.

**Parameters:**
- `$path` (string): Path to JSONL file

**Returns:** `self` - DB instance

##### `addDocument(string $id, string $text, array $embedding, array $metadata = []): void`
Add document with embedding.

**Parameters:**
- `$id` (string): Unique document identifier
- `$text` (string): Document text content
- `$embedding` (array): Vector embedding
- `$metadata` (array): Optional metadata

##### `search(string $query, int $topK = 10, array $filter = []): array`
Search for similar documents.

**Parameters:**
- `$query` (string): Search query text
- `$topK` (int): Number of results to return
- `$filter` (array): Metadata filters

**Returns:** `array` - Search results with scores

##### `getDocument(string $id): ?array`
Get document by ID.

**Parameters:**
- `$id` (string): Document ID

**Returns:** `array|null` - Document data or null if not found

##### `getAllDocuments(): array`
Get all documents.

**Returns:** `array` - All documents

##### `getDocumentCount(): int`
Get document count.

**Returns:** `int` - Number of documents

##### `close(): void`
Close database connection.

##### `createIndexes(): void`
Create indexes for better performance.

##### `exportToJsonl(string $outputPath): void`
Export to JSONL format.

**Parameters:**
- `$outputPath` (string): Output file path

##### `exportToCsv(string $outputPath): void`
Export to CSV format.

**Parameters:**
- `$outputPath` (string): Output file path

### VectorMath

Pure PHP vector math operations with SIMD-style optimizations.

#### Methods

##### `cosineSimilarity(array $a, array $b): float`
Calculate cosine similarity between two vectors.

**Parameters:**
- `$a` (array): First vector
- `$b` (array): Second vector

**Returns:** `float` - Cosine similarity score

##### `dotProduct(array $a, array $b): float`
Calculate dot product of two vectors.

**Parameters:**
- `$a` (array): First vector
- `$b` (array): Second vector

**Returns:** `float` - Dot product

##### `norm(array $vector): float`
Calculate L2 norm of a vector.

**Parameters:**
- `$vector` (array): Input vector

**Returns:** `float` - L2 norm

##### `normalize(array $vector): array`
Normalize vector to unit length.

**Parameters:**
- `$vector` (array): Input vector

**Returns:** `array` - Normalized vector

##### `euclideanDistance(array $a, array $b): float`
Calculate Euclidean distance between two vectors.

**Parameters:**
- `$a` (array): First vector
- `$b` (array): Second vector

**Returns:** `float` - Euclidean distance

##### `packVector(array $vector): string`
Pack float array to binary format for storage.

**Parameters:**
- `$vector` (array): Input vector

**Returns:** `string` - Packed binary data

##### `unpackVector(string $data, int $dimension): array`
Unpack binary data to float array.

**Parameters:**
- `$data` (string): Packed binary data
- `$dimension` (int): Vector dimension

**Returns:** `array` - Unpacked vector

##### `mean(array $vectors): array`
Calculate mean of vectors.

**Parameters:**
- `$vectors` (array): Array of vectors

**Returns:** `array` - Mean vector

### EmbedderInterface

Interface for text embedders.

#### Methods

##### `embed(string $text): array`
Generate embedding for given text.

**Parameters:**
- `$text` (string): Input text

**Returns:** `array` - Vector embedding

##### `embedBatch(array $texts): array`
Generate embeddings for multiple texts.

**Parameters:**
- `$texts` (array): Array of input texts

**Returns:** `array` - Array of vector embeddings

##### `getDimension(): int`
Get embedding dimension.

**Returns:** `int` - Embedding dimension

##### `getModelName(): string`
Get model name.

**Returns:** `string` - Model name

## Usage Examples

### Basic Usage

```php
use PhpEmbeddings\Core\DB;
use PhpEmbeddings\Embedders\BuiltinSmallEmbedder;

// Open database
$db = DB::open('vectors.sqlite');

// Add document
$embedder = new BuiltinSmallEmbedder();
$embedding = $embedder->embed("Machine learning is fascinating");
$db->addDocument("doc1", "Machine learning is fascinating", $embedding, ["category" => "ai"]);

// Search
$results = $db->search("artificial intelligence", 5);
foreach ($results as $result) {
    echo "{$result['id']}: {$result['score']} - {$result['text']}\n";
}

$db->close();
```

### With Filters

```php
// Search with metadata filter
$results = $db->search("machine learning", 10, ["category" => "ai"]);
```

### Export Data

```php
// Export to JSONL
$db->exportToJsonl('export.jsonl');

// Export to CSV
$db->exportToCsv('export.csv');
```
