<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue API supports an assortment of back-ends via a single
    | API, giving you convenient access to each back-end using the same
    | syntax for every one. Here you may define a default connection.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'http_cloud_tasks'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    | Drivers: "google_http_cloud_tasks", "google_app_engine_cloud_tasks", "sync",
    | "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],

        'app_engine_tasks' => [
            'driver' => 'google_app_engine_cloud_tasks',
            'queue' => env('GOOGLE_CLOUD_TASKS_QUEUE', 'default'),
            'project_id' => env('GOOGLE_CLOUD_TASKS_PROJECT_ID', ''),
            'location' => env('GOOGLE_CLOUD_TASKS_LOCATION_ID', ''),
            'options' => [
                'credentials' => 'path/to/your/keyfile',
                'transport' => 'rest',
            ],
        ],

        'http_cloud_tasks' => [
            'driver' => 'google_http_cloud_tasks',
            'queue' => env('GOOGLE_CLOUD_TASKS_QUEUE', 'default'),
            'project_id' => env('GOOGLE_CLOUD_TASKS_PROJECT_ID', ''),
            'location' => env('GOOGLE_CLOUD_TASKS_LOCATION_ID', ''),
            'authentication' => [
                'token_type' => 'oidc',
                'service_account' => env('GOOGLE_CLOUD_TASKS_SERVICE_ACCOUNT', ''),
            ],
            'options' => [
                'credentials' => 'path/to/your/keyfile',
                'transport' => 'rest',
            ],
            'domain' => env('GOOGLE_CLOUD_TASKS_DOMAIN'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],
];
