.PHONY: help install test test-coverage lint lint-fix stan cs-fix qa clean

help: ## Show this help message
	@echo "Available commands:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

install: ## Install dependencies
	composer install

test: ## Run tests
	composer run test

test-coverage: ## Run tests with coverage
	composer run test-coverage

lint: ## Run linting
	composer run lint

lint-fix: ## Fix linting issues
	composer run lint-fix

stan: ## Run static analysis
	composer run stan

cs-fix: ## Fix code style
	composer run cs-fix

qa: ## Run all quality assurance checks
	composer run qa

clean: ## Clean build artifacts
	rm -rf vendor/
	rm -rf coverage/
	rm -rf build/
	rm -rf dist/
	rm -f *.phar
	rm -f data/*.sqlite
	rm -f data/*.jsonl
	rm -f data/*.csv
	rm -f data/sample/*
	rm -f data/test/*

build: ## Build the project
	composer install --no-dev --optimize-autoloader

bench: ## Run benchmarks
	php bin/pe bench --db data/vectors.sqlite

demo: ## Run demo with sample data
	@echo "Creating sample data..."
	@echo "id,text,category" > data/sample.csv
	@echo "1,\"Lorem ipsum dolor sit amet\",docs" >> data/sample.csv
	@echo "2,\"Consectetur adipiscing elit\",docs" >> data/sample.csv
	@echo "3,\"Sed do eiusmod tempor incididunt\",tutorial" >> data/sample.csv
	@echo "Building vector database..."
	php bin/pe build --csv data/sample.csv --id-col id --text-col text --meta-cols category --out data/demo.sqlite
	@echo "Querying database..."
	php bin/pe query --db data/demo.sqlite --q "lorem ipsum knowledge base" --topk 3
