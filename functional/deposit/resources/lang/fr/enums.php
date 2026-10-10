<?php

return [
    'statuses' => [
        'collected' => 'Encaissée',
        'to_refund' => 'À restituer',
        'blocked_by_damage' => 'Bloquée par un dégât',
        'to_settle' => 'À solder',
        'refunded' => 'Restituée',
        'settled' => 'Soldée',
    ],
    'payment_methods' => [
        'cheque' => 'Chèque',
        'card_imprint' => 'Empreinte bancaire',
        'cash' => 'Espèces',
    ],
    'situations' => [
        'not_required' => 'Non requise (client professionnel)',
        'not_tracked' => 'Non suivie (réservation sortie avant la mise en service)',
        'customer_type_missing' => 'Type de client à renseigner',
        'to_collect' => 'À encaisser',
        'tracked' => 'Suivie',
    ],
];
