<?php

return [
    'transitions' => [
        'send' => 'transmettre',
        'fail' => 'marquer en échec',
        'requeue' => 'relancer',
        'export' => 'exporter',
    ],
    'unreachable' => 'Logiciel de facturation injoignable.',
    'missing_go_live_date' => 'La date de mise en service de la facturation (BILLING_GO_LIVE_DATE) n\'est pas configurée : rien n\'est transmis.',
    'refusals' => [
        'illegal_transition' => 'Impossible de :transition une transmission à l\'état « :status ».',
    ],
];
