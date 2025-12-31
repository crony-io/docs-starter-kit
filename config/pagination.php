<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Pagination Values
    |--------------------------------------------------------------------------
    |
    | These values define the default pagination limits for various sections
    | of the application. Modify these values to change pagination globally.
    |
    */

    'pages' => 20,
    'feedback' => 20,
    'media' => 24,
    'activity_logs' => 15,
    'users' => 15,

    /*
    |--------------------------------------------------------------------------
    | Allowed Per Page Options
    |--------------------------------------------------------------------------
    |
    | These values define the allowed options for per-page selections
    | in paginated views.
    |
    */

    'allowed_per_page' => [15, 25, 50, 100],
];
