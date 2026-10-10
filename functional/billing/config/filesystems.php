<?php

return [
    'disks' => [
        'billing-exports' => [
            'driver' => 'local',
            'root' => storage_path('app/private/billing-exports'),
            'throw' => true,
            'report' => false,
        ],
    ],
];
