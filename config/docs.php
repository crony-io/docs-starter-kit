<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Git Sync Configuration
    |--------------------------------------------------------------------------
    */
    'git_enabled' => env('DOCS_GIT_ENABLED', true),
    'git_clone_path' => storage_path('app/git'),
    'git_max_repo_size' => env('DOCS_MAX_REPO_SIZE', 500), // MB

    /*
    |--------------------------------------------------------------------------
    | Content Mode
    |--------------------------------------------------------------------------
    */
    'content_mode' => env('DOCS_CONTENT_MODE', 'cms'), // git or cms

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    */
    'sync_frequency' => env('DOCS_GIT_SYNC_FREQUENCY', 15), // minutes
    'sync_timeout' => 300, // seconds

    /*
    |--------------------------------------------------------------------------
    | Markdown Configuration
    |--------------------------------------------------------------------------
    */
    'markdown_extensions' => [
        'tables' => true,
        'strikethrough' => true,
        'task_lists' => true,
    ],
];
