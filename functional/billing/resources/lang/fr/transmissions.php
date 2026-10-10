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
