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
    'deactivate_heading' => 'Désactiver le compte ?',
    'deactivate_confirmation' => ':name ne pourra plus se connecter et sera déconnecté de ses sessions ouvertes. Vous pourrez réactiver le compte plus tard.',
    'keep_active' => 'Garder le compte actif',
    'confirm_deactivation' => 'Désactiver le compte',
    'deactivated_toast' => 'Compte de :name désactivé.',
    'reactivated_toast' => 'Compte de :name réactivé.',
    'agency_changed' => ':name est maintenant rattaché à l\'agence :agency.',
    'agency_of' => 'Agence de :name',
    'refusals' => [
        'deactivated' => 'Ce compte est désactivé.',
        'self_deactivation' => 'Vous ne pouvez pas désactiver votre propre compte.',
    ],
];
