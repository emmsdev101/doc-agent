<?php

return [
    'rag' => [
        'top_k' => (int) env('RAG_TOP_K', 5),
        'min_similarity' => (float) env('RAG_MIN_SIMILARITY', 0.15),
        'system_prompt' => 'You are a helpful knowledge-base assistant. Answer using only the provided context. If the context is missing or you are unsure, say you do not know. Do not invent facts.',
    ],

    'chunking' => [
        'target_tokens' => (int) env('DOCUMENT_CHUNK_TOKENS', 750),
        'overlap_tokens' => (int) env('DOCUMENT_CHUNK_OVERLAP', 80),
        'min_tokens' => 40,
    ],

    'uploads' => [
        'disk' => env('DOCUMENTS_DISK', 'documents'),
        'max_kilobytes' => 20480,
        'mimes' => ['pdf', 'txt', 'md', 'docx'],
    ],
];
