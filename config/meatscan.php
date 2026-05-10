<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MeatScan Detector Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "openai_vision", "mock"
    |
    | - openai_vision: real AI classification using OpenAI vision models
    | - mock: deterministic mock results (no external calls)
    |
    */
    'driver' => env('MEATSCAN_DETECTOR', 'openai_vision'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Vision Settings
    |--------------------------------------------------------------------------
    */
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_VISION_MODEL', 'gpt-4o-mini'),
        'endpoint' => env('OPENAI_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
        'timeout_seconds' => (int) env('OPENAI_TIMEOUT', 45),
        'max_image_bytes' => (int) env('OPENAI_MAX_IMAGE_BYTES', 3_000_000),
    ],
];

