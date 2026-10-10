<?php

use Functional\Billing\Gateways\FakeBillingGateway;

return [
    'go_live_date' => env('BILLING_GO_LIVE_DATE'),
    'gateway' => env('BILLING_GATEWAY'),
    'gateways' => [
        'fake' => FakeBillingGateway::class,
    ],
    'timezone' => 'Europe/Paris',
    'gateway_timeout_seconds' => 10,
    'reservation_margin_seconds' => 30,
    'retry_delays_minutes' => [1, 5, 15, 60],
    'alert_after_hours' => 24,
    'damage_overdue_days' => 7,
    'export_disk' => 'billing-exports',
];
