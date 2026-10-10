<?php

return [
    'go_live_date' => env('CERTIFICATION_GO_LIVE_DATE'),
    'timezone' => 'Europe/Paris',
    'retry_delays_minutes' => [1, 5, 15, 60],
    'alert_after_minutes' => 60,
    'accepted_mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
    'max_report_kilobytes' => 10240,
    'reports_disk' => 'vgp-reports',
];
