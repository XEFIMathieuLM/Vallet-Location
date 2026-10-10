<?php

return [
    'transitions' => [
        'send' => 'transmettre',
        'fail' => 'marquer en échec',
        'requeue' => 'relancer',
        'export' => 'exporter',
    ],
    'screen' => [
        'title' => 'Transmissions à traiter',
        'intro' => 'Transmissions en échec et transmissions en attente depuis plus de :hours heures.',
        'empty' => 'Aucune transmission à traiter : tout ce qui a été produit est parti ou attend moins de 24 heures.',
        'open_statement' => 'Voir le relevé de facturation',
        'customer_ref_saved' => 'Référence client enregistrée. Relancez la transmission pour l\'envoyer.',
        'retried' => 'Transmission relancée : elle part dans quelques instants.',
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
    'reasons' => [
        'customer_unknown' => 'Client inconnu du logiciel de facturation : renseignez sa référence client, puis relancez.',
        'rejected' => 'Refusée par le logiciel de facturation : corrigez la donnée en cause dans le logiciel, puis relancez.',
        'unreachable' => 'Logiciel de facturation injoignable : nouvelle tentative automatique le :date.',
        'waiting' => 'En attente d\'envoi automatique.',
        'technical_detail' => 'Détail technique',
    ],
    'alert' => [
        'count' => ':count transmission à traiter|:count transmissions à traiter',
        'open' => 'Voir les transmissions',
    ],
    'refusals' => [
        'illegal_transition' => 'Impossible de :transition une transmission à l\'état « :status ».',
    ],
];
