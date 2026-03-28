<?php

return [
    'base_url' => env('PISTON_BASE_URL', 'http://127.0.0.1:2000/api/v2'),

    'http_timeout_seconds' => (int) env('PISTON_HTTP_TIMEOUT_SECONDS', 10),

    'runtimes' => [
        'c' => [
            'language' => env('PISTON_LANGUAGE_C', 'c'),
            'version' => env('PISTON_VERSION_C', '*'),
        ],
        'cpp' => [
            'language' => env('PISTON_LANGUAGE_CPP', 'cpp'),
            'version' => env('PISTON_VERSION_CPP', '*'),
        ],
        'java' => [
            'language' => env('PISTON_LANGUAGE_JAVA', 'java'),
            'version' => env('PISTON_VERSION_JAVA', '*'),
        ],
        'python' => [
            'language' => env('PISTON_LANGUAGE_PYTHON', 'python'),
            'version' => env('PISTON_VERSION_PYTHON', '*'),
        ],
        'typescript' => [
            'language' => env('PISTON_LANGUAGE_TYPESCRIPT', 'typescript'),
            'version' => env('PISTON_VERSION_TYPESCRIPT', '*'),
        ],
    ],
];
