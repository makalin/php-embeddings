# php-embeddings

**CLI to convert CSV → OpenAI-compatible vector DB.**
**Zero external services. Uses pure-PHP SIMD-style vector ops for speed.**

![License](https://img.shields.io/badge/license-MIT-informational)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4)
![Status](https://img.shields.io/badge/status-alpha-yellow)
![Platform](https://img.shields.io/badge/platform-CLI-black)

---

## Why

Turn any CSV into a local, portable vector database that mirrors the common *OpenAI embeddings* shape:

```json
{ "id": "doc_123", "embedding": [ ... floats ... ], "metadata": { ... } }
```

Outputs **SQLite** or **JSONL** with fast cosine-similarity search—no Python, no FAISS. Great for quick RAG prototypes, small/medium datasets, or PHP-native stacks.

---

## Features

* **CSV → vectors** with pluggable text fields and optional metadata passthrough
* **OpenAI-compatible schema** (`id`, `embedding`, optional `metadata`, `text`)
* **Backends:** SQLite (default) or JSONL
* **Search:** cosine similarity (top-k), filtering by metadata keys
* **Pure PHP vector math** (packed binary + array ops) — works anywhere PHP runs
* **Deterministic models**: built-in small fast embedder + hooks to swap in others
* **Streaming**: processes large CSVs row-by-row (low memory)

---

## Install

### Composer (recommended)

```bash
composer create-project --no-dev makalin/php-embeddings ./php-embeddings
# or, if published as a package:
composer global require makalin/php-embeddings
```

### Manual

```bash
git clone https://github.com/makalin/php-embeddings.git
cd php-embeddings
php bin/pe --help
```

**Requires:** PHP 8.2+ with `mbstring`, `json`, `pdo_sqlite` (for SQLite backend).

---

## Quick Start

### 1) Prepare a CSV

Minimal columns:

```csv
id,text,category
1,"Lorem ipsum dolor sit amet","docs"
2,"Consectetur adipiscing elit","docs"
```

### 2) Build the vector DB

```bash
# SQLite (default)
php bin/pe build \
  --csv data.csv \
  --id-col id \
  --text-col text \
  --out vectors.sqlite

# JSONL (portable)
php bin/pe build \
  --csv data.csv \
  --id-col id \
  --text-col text \
  --out vectors.jsonl \
  --format jsonl
```

### 3) Query (top-k)

```bash
php bin/pe query \
  --db vectors.sqlite \
  --q "lorem ipsum knowledge base" \
  --topk 5
```

Output (JSON):

```json
{
  "query": "lorem ipsum knowledge base",
  "results": [
    { "id": "2", "score": 0.8731, "text": "Consectetur adipiscing elit", "metadata": {"category":"docs"} },
    { "id": "1", "score": 0.8615, "text": "Lorem ipsum dolor sit amet", "metadata": {"category":"docs"} }
  ]
}
```

---

## CLI Usage

```bash
php bin/pe build \
  --csv <path> \
  --id-col <name> \
  --text-col <name> \
  [--meta-cols col1,col2,...] \
  [--out vectors.sqlite] \
  [--format sqlite|jsonl] \
  [--dim 384] \
  [--model builtin-small] \
  [--batch 1024] \
  [--normalize] \
  [--append] \
  [--no-index]   # skip creating SQLite indexes (faster import)

php bin/pe query \
  --db <vectors.sqlite|vectors.jsonl> \
  --q "your search text" \
  [--topk 10] \
  [--filter "category=docs,lang=en"]
```

**Notes**

* `--meta-cols` copies CSV columns into `metadata`.
* `--normalize` L2-normalizes embeddings (faster cosine ≈ dot).
* `--dim` is the embedding vector size (model dependent).

---

## Data Model

### SQLite

* `documents(id TEXT PRIMARY KEY, text TEXT, metadata JSON)`
* `embeddings(id TEXT PRIMARY KEY, dim INTEGER, vec BLOB)`

  * `vec` stores `dim` little-endian `float32` packed via `pack('f*', ...)`
* Indexes: `CREATE INDEX IF NOT EXISTS idx_docs_meta ON documents(json_extract(metadata,'$.category'));`

### JSONL

Each line:

```json
{ "id": "1", "text": "…", "embedding": [0.01, -0.02, ...], "metadata": {"category":"docs"} }
```

---

## OpenAI-Compatible?

* The shape (`id`, `embedding`, optional `metadata`, `text`) matches common OpenAI embeddings usage.
* You can **export** to the same JSONL style many OpenAI-based tools expect.
* If you already have OpenAI embeddings, you can **import** them with:

```bash
php bin/pe import:jsonl --in openai.jsonl --out vectors.sqlite
```

---

## PHP API (optional)

```php
use PhpEmbeddings\DB;

$db = DB::open('vectors.sqlite');        // or DB::openJsonl('vectors.jsonl')
$results = $db->search('lorem ipsum', topK: 5, filter: ['category' => 'docs']);
foreach ($results as $r) {
    echo "{$r->id} {$r->score} {$r->text}\n";
}
```

---

## Performance

* **SIMD-style math in pure PHP:** packs float arrays into binary and does vector ops in tight loops to reduce overhead.
* **Batching:** embeddings computed in batches to limit allocations.
* **Tip:** use `php -d detect_unicode=0 -d memory_limit=2G` for very large CSVs.

> Target scale: \~1–2 million rows on commodity hardware (SQLite). JSONL recommended for portability; SQLite recommended for speed.

---

## Import/Export

```bash
# Export SQLite → JSONL
php bin/pe export:jsonl --db vectors.sqlite --out vectors.jsonl

# Export SQLite → CSV (with flattened metadata)
php bin/pe export:csv --db vectors.sqlite --out vectors.csv
```

---

## Benchmarks (placeholder)

* Build: 100k rows, dim=384 → \~X min on M-series laptop
* Query: top-k=10 over 100k rows → \~Y ms

> Run your own: `php bin/pe bench --db vectors.sqlite`

---

## Roadmap

* HNSW/IVF approximate search (pure PHP)
* Optional quantization (int8)
* Additional models (multilingual)
* Parquet backend
* HTTP microserver (`pe serve`)

---

## Troubleshooting

* **Memory spikes on huge CSVs** → add `--batch`, ensure `auto_detect_line_endings=1`.
* **Slow query on JSONL** → switch to SQLite or pre-normalize (`--normalize`).
* **Non-UTF8 CSV** → re-encode with `iconv -f WINDOWS-1254 -t UTF-8`.

---

## Development

```bash
make test
make lint
composer run qa
```

---

## License

MIT © 2025 Mehmet T. AKALIN

---

## Credits

* Inspired by OpenAI embeddings ecosystem and numerous OSS vector DB schemas.
* Built for PHP-first workflows needing a minimal, portable vector store.
