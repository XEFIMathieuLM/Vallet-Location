<?php

$isLocalDisk = env('PHOTOS_DISK_DRIVER', 'local') === 'local';

return [
    'disks' => [
        'photos' => [
            'driver' => env('PHOTOS_DISK_DRIVER', 'local'),
            'root' => $isLocalDisk ? storage_path('app/private/photos') : env('PHOTOS_S3_ROOT', ''),
            'url' => rtrim((string) env('APP_URL', 'http://localhost'), '/').'/photo-files',
            'serve' => true,
            'visibility' => 'private',
            'key' => env('PHOTOS_S3_ACCESS_KEY_ID'),
            'secret' => env('PHOTOS_S3_SECRET_ACCESS_KEY'),
            'region' => env('PHOTOS_S3_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('PHOTOS_S3_BUCKET'),
            'endpoint' => env('PHOTOS_S3_ENDPOINT'),
            'use_path_style_endpoint' => env('PHOTOS_S3_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => true,
            'report' => false,
        ],
    ],
];
