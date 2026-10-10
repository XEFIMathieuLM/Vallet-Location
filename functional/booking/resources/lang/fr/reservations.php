<?php

return [
    'status' => [
        'confirmed' => 'Confirmée',
        'in_progress' => 'En cours',
        'closed' => 'Clôturée',
        'cancelled' => 'Annulée',
    ],

    'conflicts' => [
        'machine_unavailable' => 'Machine indisponible',
        'vgp_expired' => 'VGP non valide sur la période',
        'machine_not_returned' => 'Machine pas encore rentrée',
    ],

    'refusals' => [
        'overlap' => 'Machine déjà réservée du :start au :end par l\'agence :agency.',
        'start_in_the_past' => 'La date de début ne peut pas être dans le passé.',
        'end_before_start' => 'La date de fin doit être postérieure ou égale à la date de début.',
        'machine_status' => 'Machine non réservable : elle est « :status ».',
        'vgp_missing' => 'Machine non réservable : sa VGP n\'est pas renseignée.',
        'vgp_expires' => 'Machine non réservable : sa VGP expire le :date, avant la fin de la période.',
        'machine_not_returned' => 'Machine non réservable : elle n\'est pas encore rentrée de sa location précédente.',
    ],
];
