<?php

return [
    // Database settings
    'database' => [
        'path' => 'vectors.sqlite',
        'format' => 'sqlite', // sqlite or jsonl
    ],

    // Embedding settings
    'embedding' => [
        'dimension' => 384,
        'model' => 'builtin-small',
        'normalize' => false,
    ],

    // Processing settings
    'processing' => [
        'batch_size' => 1024,
        'memory_limit' => '2G',
        'auto_detect_line_endings' => true,
    ],

    // Performance settings
    'performance' => [
        'create_indexes' => true,
        'use_transactions' => true,
    ],

    // Logging
    'logging' => [
        'level' => 'info',
        'file' => 'logs/php-embeddings.log',
    ],
];
