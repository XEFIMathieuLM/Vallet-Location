<?php

return [
    'disks' => [
        'vgp-reports' => [
            'driver' => 'local',
            'root' => storage_path('app/private/vgp-reports'),
            'throw' => true,
            'report' => false,
        ],
    ],
];
