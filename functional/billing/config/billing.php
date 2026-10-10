<?php

return [
    'go_live_date' => env('BILLING_GO_LIVE_DATE'),
    'gateway' => env('BILLING_GATEWAY', 'fake'),
    'http_timeout_seconds' => 10,
    'retry_delays_minutes' => [1, 5, 15, 60],
    'alert_after_hours' => 24,
    'damage_overdue_days' => 7,
    'export_disk' => 'billing-exports',
];
