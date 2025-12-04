<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Firebase project
    |--------------------------------------------------------------------------
    |
    | This is the default project that will be used when you call
    | Firebase::project() or Firebase::messaging(), etc.
    |
    */

    'default' => 'app',

    /*
    |--------------------------------------------------------------------------
    | Firebase Projects
    |--------------------------------------------------------------------------
    */

    'projects' => [
        'app' => [
            'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase-credentials.json')),
            // OR if your file is in project root:
            // 'credentials' => env('FIREBASE_CREDENTIALS', base_path('firebase-credentials.json')),

            'auth' => [
                'tenant_id' => env('FIREBASE_AUTH_TENANT_ID'),
            ],

            'database' => [
                'url' => env('FIREBASE_DATABASE_URL'),
            ],

            'dynamic_links' => [
                'default_domain' => env('FIREBASE_DYNAMIC_LINKS_DEFAULT_DOMAIN'),
            ],

            'storage' => [
                'default_bucket' => env('FIREBASE_STORAGE_DEFAULT_BUCKET'),
            ],

            'cache_store' => env('FIREBASE_CACHE_STORE', 'file'),

            'logging' => [
                'http_log_channel' => env('FIREBASE_HTTP_LOG_CHANNEL'),
                'http_debug_log_channel' => env('FIREBASE_HTTP_DEBUG_LOG_CHANNEL'),
            ],

            'http_client_options' => [
                'proxy' => env('FIREBASE_HTTP_CLIENT_PROXY'),
                'timeout' => env('FIREBASE_HTTP_CLIENT_TIMEOUT'),
            ],
        ],
    ],
];