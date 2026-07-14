<?php

return [
    'paths' => [
        resource_path('views'),
    ],

    /*
     * Compiled views are stored OUTSIDE OneDrive to avoid Windows file-lock
     * errors caused by OneDrive syncing .tmp files during rename().
     */
    'compiled' => env('VIEW_COMPILED_PATH', 'C:\\laravel-cache\\msas-views'),
];
