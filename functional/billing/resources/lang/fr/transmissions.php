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
    'screen' => [
        'title' => 'Transmissions à traiter',
        'intro' => 'Transmissions en échec et transmissions en attente depuis plus de :hours heures.',
        'empty' => 'Aucune transmission à traiter.',
        'reservation' => 'Réservation',
        'customer' => 'Client',
        'type' => 'Type',
        'date' => 'Date',
        'reason' => 'État et motif',
        'damage' => 'Dégât',
        'customer_ref' => 'Référence client du logiciel de facturation',
        'save_customer_ref' => 'Enregistrer',
        'retry' => 'Relancer',
    ],
    'alert' => [
        'count' => ':count transmission à traiter|:count transmissions à traiter',
        'open' => 'Voir les transmissions',
    ],
    'refusals' => [
        'illegal_transition' => 'Impossible de :transition une transmission à l\'état « :status ».',
    ],
];
