<?php

return [
    'statuses' => [
        'hidden' => 'Sans objet',
        'optional' => 'Facultatif',
        'required' => 'Exigé, à saisir',
        'entered' => 'Saisi',
        'frozen' => 'Figé à la sortie',
    ],
    'section' => [
        'title' => 'Bon de commande',
        'key_account' => 'Grand compte : tarif négocié appliqué par la facturation. Le numéro de bon de commande est exigé avant la sortie.',
        'number' => 'Numéro de bon de commande',
        'enter' => 'Enregistrer',
        'correct' => 'Corriger',
        'entered_by' => 'Saisi par :author (:agency) le :date',
        'saved' => 'Bon de commande :number enregistré.',
    ],
    'missing' => [
        'title' => 'Bons de commande manquants',
        'navigation' => 'Bons de commande',
        'intro' => 'Réservations confirmées de grands comptes sans numéro de bon de commande, par date de départ. Les départs dans :days jours ou moins sont mis en évidence.',
        'agency' => 'Agence de rattachement',
        'all_agencies' => 'Toutes les agences',
        'departure' => 'Départ',
        'customer' => 'Client',
        'machine' => 'Machine',
        'empty_heading' => 'Aucun bon de commande manquant',
        'empty' => 'Toutes les réservations confirmées de grands comptes ont leur numéro de bon de commande.',
    ],
];
