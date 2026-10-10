<?php

return [
    'statuses' => [
        'pending' => 'En attente',
        'confirmed' => 'Confirmée',
        'refused' => 'Refusée',
        'cancelled' => 'Annulée',
        'expired' => 'Expirée',
    ],
    'history' => [
        'request_sent' => 'Demande envoyée en ligne',
        'request_confirmed' => 'Demande confirmée : réservation n° :reservation_id',
        'account_attached' => 'Compte client :account_email rattaché à la fiche',
        'request_refused' => 'Demande refusée : :reason',
        'request_cancelled' => 'Demande annulée par le client',
        'request_expired' => 'Demande expirée sans décision de l\'agence',
        'indicative_price_set' => 'Prix indicatif fixé à :new_price',
        'indicative_price_removed' => 'Prix indicatif retiré',
    ],
];
