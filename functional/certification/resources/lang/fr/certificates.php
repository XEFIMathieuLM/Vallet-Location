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
        'customer_email' => 'E-mail du client',
        'save_email' => 'Enregistrer l\'e-mail',
        'email_saved' => 'E-mail du client enregistré.',
        'hand_delivery' => 'Rapport VGP remis en main propre',
        'hand_delivery_confirm' => 'Confirmez que le rapport de VGP en vigueur a été remis au client. La remise est tracée dans l\'historique et débloque la sortie.',
        'confirm_hand_delivery' => 'Confirmer la remise',
        'cancel' => 'Annuler',
        'hand_delivered' => 'Remise en main propre enregistrée.',
    ],
    'transitions' => [
        'await_email' => 'mettre en attente d\'e-mail',
        'queue' => 'mettre en file d\'envoi',
        'send' => 'marquer envoyée',
        'fail' => 'marquer en échec',
        'hand_deliver' => 'enregistrer la remise en main propre de',
    ],
    'refusals' => [
        'not_delivered' => [
            'awaiting_report' => 'Sortie refusée : attestation VGP non envoyée, rapport de VGP non déposé pour la machine.',
            'awaiting_email' => 'Sortie refusée : attestation VGP non envoyée, e-mail du client manquant. Renseignez-le ou enregistrez la remise en main propre du rapport.',
            'pending' => 'Sortie refusée : attestation VGP non envoyée, envoi en attente.',
            'failed' => 'Sortie refusée : attestation VGP non envoyée, envoi en échec (:reason).',
        ],
        'hand_delivery' => [
            'no_report' => 'Remise impossible : aucun rapport de VGP n\'est déposé pour cette machine.',
            'not_confirmed' => 'Remise impossible : la réservation n\'est pas confirmée.',
            'already_delivered' => 'L\'attestation VGP a déjà été envoyée ou remise.',
            'no_certificate' => 'Remise impossible : aucune attestation n\'est ouverte pour cette réservation.',
        ],
        'illegal_transition' => 'Impossible de :transition l\'attestation VGP : elle est à l\'état « :status ».',
    ],
];
