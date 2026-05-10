<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MeatScan Detector Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "roboflow_workflow", "roboflow_universe", "openai_vision", "mock"
    |
    | - roboflow_workflow: Roboflow Workflows via serverless.roboflow.com
    | - roboflow_universe: real AI classification using Roboflow hosted API
    | - openai_vision: real AI classification using OpenAI vision models
    | - mock: deterministic mock results (no external calls)
    |
    */
    'driver' => env('MEATSCAN_DETECTOR', 'roboflow_workflow'),

    /*
    |--------------------------------------------------------------------------
    | Roboflow Workflows (Serverless) Settings
    |--------------------------------------------------------------------------
    |
    | Endpoint format (from Roboflow):
    |   POST https://serverless.roboflow.com/{workspace}/workflows/{workflow}
    |
    | Body:
    | {
    |   "api_key": "...",
    |   "inputs": { "image": {"type":"url|base64","value":"..."} }
    | }
    |
    */
    'roboflow_workflow' => [
        'api_key' => env('ROBOFLOW_API_KEY'),
        'workspace' => env('ROBOFLOW_WORKSPACE'),
        'workflow' => env('ROBOFLOW_WORKFLOW'),
        'endpoint_base' => env('ROBOFLOW_WORKFLOW_ENDPOINT_BASE', 'https://serverless.roboflow.com'),
        'timeout_seconds' => (int) env('ROBOFLOW_TIMEOUT', 45),
        'max_image_bytes' => (int) env('ROBOFLOW_MAX_IMAGE_BYTES', 3_000_000),
        // base64 is safest since our images are stored locally
        'image_input_type' => env('ROBOFLOW_IMAGE_INPUT_TYPE', 'base64'), // base64 | url
        // optional: pass extra inputs (like "classes") if your workflow needs them (JSON string)
        'extra_inputs_json' => env('ROBOFLOW_WORKFLOW_EXTRA_INPUTS_JSON'),
        // Optional workflow parameter expected by some SAM-based steps.
        // Provide as "fresh,spoiled,uncertain" or switch format to "list".
        'classes' => env('ROBOFLOW_CLASSES'),
        'classes_format' => env('ROBOFLOW_CLASSES_FORMAT', 'string'), // string | list
        // Optional exact-match mapping from workflow class names -> MeatScan labels.
        // Example JSON in env: {"Fresh Meat":"fresh","Spoiled Meat":"spoiled"}
        'label_map' => json_decode(env('ROBOFLOW_LABEL_MAP_JSON', '[]'), true) ?: [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Roboflow Universe (Hosted Inference) Settings
    |--------------------------------------------------------------------------
    |
    | You need a Roboflow hosted model (Universe / workspace model) name and version.
    | Classification endpoint format:
    |   https://classify.roboflow.com/{model}/{version}?api_key=...
    |
    */
    'roboflow' => [
        'api_key' => env('ROBOFLOW_API_KEY'),
        'model' => env('ROBOFLOW_MODEL'),
        'version' => env('ROBOFLOW_VERSION', 1),
        'endpoint_base' => env('ROBOFLOW_ENDPOINT_BASE', 'https://classify.roboflow.com'),
        'timeout_seconds' => (int) env('ROBOFLOW_TIMEOUT', 45),
        'max_image_bytes' => (int) env('ROBOFLOW_MAX_IMAGE_BYTES', 3_000_000),
    ],

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

