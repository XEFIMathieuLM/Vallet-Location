<?php

return [
    'title' => 'Salariés',
    'fields' => [
        'name' => 'Nom',
        'email' => 'E-mail',
        'agency' => 'Agence',
        'status' => 'Statut',
    ],
    'choose_agency' => 'Choisir une agence',
    'create' => 'Créer le compte',
    'create_help' => 'Le salarié reçoit un e-mail pour choisir son mot de passe.',
    'created' => 'Compte de :name créé ; un e-mail lui a été envoyé pour choisir son mot de passe.',
    'active' => 'Actif',
    'deactivated' => 'Désactivé',
    'deactivate' => 'Désactiver',
    'reactivate' => 'Réactiver',
    'deactivate_confirmation' => 'Désactiver ce compte ? Le salarié ne pourra plus se connecter.',
    'refusals' => [
        'deactivated' => 'Ce compte est désactivé.',
        'self_deactivation' => 'Vous ne pouvez pas désactiver votre propre compte.',
    ],
];
