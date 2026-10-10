<?php

return [
    'certificate_status' => [
        'awaiting_report' => 'En attente : rapport de VGP non déposé',
        'awaiting_email' => 'Non envoyée : e-mail du client manquant',
        'pending' => 'En attente d\'envoi',
        'failed' => 'Échec de l\'envoi',
        'sent' => 'Envoyée',
        'hand_delivered' => 'Remise en main propre',
    ],
    'dispatch_channel' => [
        'email' => 'E-mail',
        'hand' => 'Main propre',
    ],
    'dispatch_outcome' => [
        'sent' => 'Envoyé',
        'failed' => 'Échec',
    ],
    'dispatch_failure_reason' => [
        'invalid_address' => 'Adresse e-mail invalide',
        'recipient_rejected' => 'Adresse refusée par la messagerie du destinataire',
        'mail_service_unavailable' => 'Service d\'envoi indisponible, nouvel essai prévu',
    ],
];
