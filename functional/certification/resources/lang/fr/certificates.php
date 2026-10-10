<?php

return [
    'section' => [
        'title' => 'Attestation VGP',
        'opening' => 'Attestation en cours d\'ouverture : envoi en attente.',
        'delivered_on' => 'le :date',
        'recipient' => 'Adresse destinataire',
        'no_email' => 'Aucune adresse e-mail',
        'last_dispatch' => 'Dernier envoi',
        'never' => 'Aucun',
        'attempts' => 'Tentatives automatiques',
    ],
    'transitions' => [
        'await_email' => 'mettre en attente d\'e-mail',
        'queue' => 'mettre en file d\'envoi',
        'send' => 'marquer envoyée',
        'fail' => 'marquer en échec',
        'hand_deliver' => 'enregistrer la remise en main propre de',
    ],
    'refusals' => [
        'illegal_transition' => 'Impossible de :transition l\'attestation VGP : elle est à l\'état « :status ».',
    ],
];
