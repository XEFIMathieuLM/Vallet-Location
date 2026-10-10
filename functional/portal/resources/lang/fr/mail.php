<?php

return [
    'greeting' => 'Bonjour :name,',
    'signature' => 'L\'équipe Vallet Location',
    'link_lifetime' => 'Ce lien est valable :minutes minutes.',
    'verify' => [
        'subject' => 'Confirmez votre adresse e-mail',
        'heading' => 'Confirmez votre adresse e-mail',
        'intro' => 'Merci d\'avoir créé votre espace client Vallet Location. Confirmez votre adresse pour accéder à la recherche de machines et envoyer vos demandes.',
        'action' => 'Confirmer mon adresse',
        'ignore' => 'Si vous n\'avez pas créé de compte, ignorez ce message.',
    ],
    'reset' => [
        'subject' => 'Réinitialisation de votre mot de passe',
        'heading' => 'Réinitialisation de votre mot de passe',
        'intro' => 'Vous avez demandé à réinitialiser le mot de passe de votre espace client.',
        'action' => 'Choisir un nouveau mot de passe',
        'ignore' => 'Si vous n\'avez rien demandé, ignorez ce message : votre mot de passe reste inchangé.',
    ],
    'decision' => [
        'machine' => 'Machine',
        'period' => 'Période',
        'period_value' => 'du :start au :end',
        'agency' => 'Agence de retrait',
        'reason' => 'Motif',
        'action' => 'Voir mes demandes',
        'confirmed' => [
            'subject' => 'Votre réservation de la machine :reference est confirmée',
            'heading' => 'Votre réservation est confirmée',
            'intro' => 'Votre agence a confirmé votre demande. La machine suivante vous est réservée :',
            'outro' => 'Votre agence vous contactera si une caution, un bon de commande ou d\'autres documents sont nécessaires avant le départ. L\'attestation VGP de la machine, si elle y est soumise, vous est envoyée séparément.',
        ],
        'refused' => [
            'subject' => 'Votre demande pour la machine :reference n\'a pas pu être acceptée',
            'heading' => 'Votre demande n\'a pas pu être acceptée',
            'intro' => 'Votre agence n\'a pas pu accepter votre demande :',
            'outro' => 'Vous pouvez chercher une autre machine ou d\'autres dates et envoyer une nouvelle demande.',
        ],
        'expired' => [
            'subject' => 'Votre demande pour la machine :reference a expiré',
            'heading' => 'Votre demande a expiré',
            'intro' => 'Votre demande n\'a pas pu être traitée avant sa date de début :',
            'outro' => 'Pour réserver une machine, envoyez une nouvelle demande ou contactez directement votre agence.',
        ],
    ],
];
