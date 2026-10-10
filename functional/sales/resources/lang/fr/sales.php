<?php

return [
    'status' => [
        'listed' => 'En vente',
        'reserved' => 'Réservée',
        'sold' => 'Vendue',
        'cancelled' => 'Annulée',
    ],
    'offer_status' => [
        'pending' => 'En cours',
        'accepted' => 'Acceptée',
        'rejected' => 'Refusée',
        'withdrawn' => 'Retirée',
    ],
    'transitions' => [
        'reserve' => 'réserver',
        'release' => 'lever la réservation',
        'sell' => 'conclure',
        'cancel' => 'annuler',
    ],
    'offer_transitions' => [
        'accept' => 'accepter',
        'reject' => 'refuser',
        'withdraw' => 'retirer',
    ],
];
