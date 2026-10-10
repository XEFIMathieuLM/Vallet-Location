<?php

return [
    'navigation' => 'Dégâts',
    'resolve' => 'Marquer traité',
    'resolve_confirm' => 'Marquer ce dégât comme traité ?',
    'reported' => 'Signalé par :author le :date',
    'resolved' => 'Traité par :author le :date',

    'comparison' => [
        'title' => 'Photos départ / retour : :reference',
        'taken' => 'Prise le :date par :author',
        'damages' => 'Dégâts signalés',
    ],

    'report' => [
        'title' => 'Signaler un dégât',
        'view' => 'Vue concernée',
        'choose_view' => 'Choisir une vue',
        'comment' => 'Commentaire',
        'submit' => 'Signaler',
        'done' => 'Dégât signalé.',
    ],

    'list' => [
        'title' => 'Dégâts à traiter',
        'agency' => 'Agence :agency',
        'empty' => 'Aucun dégât à traiter. Les dégâts signalés depuis la comparaison des photos de départ et de retour apparaîtront ici.',
        'offline' => 'Connexion perdue : la liste ne se met plus à jour. Vérifiez votre connexion puis rechargez la page.',
    ],

    'refusals' => [
        'return_photos_incomplete' => 'Un dégât ne peut être signalé qu\'une fois toutes les photos de retour prises. Prenez les photos manquantes depuis le détail de la réservation, puis revenez sur cette page.',
        'empty_comment' => 'Le commentaire est obligatoire.',
        'already_resolved' => 'Ce dégât a déjà été traité. Rechargez la page pour voir la liste à jour.',
    ],
];
