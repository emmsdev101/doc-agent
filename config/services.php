<?php

return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'deepseek' => [
        'key' => env('DEEPSEEK_API_KEY'),
        'base_url' => rtrim((string) env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'), '/'),
        'chat_model' => env('DEEPSEEK_CHAT_MODEL', 'deepseek-chat'),
        'timeout' => 60,
    ],

    'embeddings' => [
        'driver' => env('EMBEDDING_DRIVER', 'local'),
        'key' => env('EMBEDDING_API_KEY', env('DEEPSEEK_API_KEY')),
        'base_url' => rtrim((string) env('EMBEDDING_BASE_URL', 'https://api.openai.com/v1'), '/'),
        'model' => env('EMBEDDING_MODEL', 'text-embedding-3-small'),
        'dimensions' => (int) env('EMBEDDING_DIMENSIONS', 1536),
        'batch_size' => (int) env('EMBEDDING_BATCH_SIZE', 16),
        'timeout' => 60,
        'query_task' => env('EMBEDDING_QUERY_TASK', 'retrieval.query'),
        'passage_task' => env('EMBEDDING_PASSAGE_TASK', 'retrieval.passage'),
    ],
];
