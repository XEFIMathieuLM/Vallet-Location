<?php

return [
    'title' => 'Tableau de bord',
    'agency' => [
        'label' => 'Agence',
        'all' => 'Toutes les agences',
    ],
    'empty' => [
        'heading' => 'Aucune information disponible',
        'description' => 'Votre compte n\'a accès à aucun des écrans résumés sur ce tableau de bord.',
    ],
    'section' => [
        'more' => ':shown sur :total',
    ],
    'operations' => [
        'heading' => 'Opérations',
        'departures' => 'Départs du jour',
        'upcoming_departures' => 'Départs à venir (:days jours)',
        'returns' => 'Retours du jour',
        'late_returns' => 'Retours en retard',
        'conflicts' => 'Réservations en conflit',
        'empty' => [
            'departures' => 'Aucun départ prévu aujourd\'hui.',
            'upcoming_departures' => 'Aucun départ prévu dans les :days prochains jours.',
            'returns' => 'Aucun retour prévu aujourd\'hui.',
            'late_returns' => 'Aucun retour en retard.',
            'conflicts' => 'Aucune réservation en conflit.',
        ],
        'planned_on' => 'départ prévu le :date',
        'days_late' => '{1} :count jour de retard|[2,*] :count jours de retard',
        'see_all' => 'Voir toutes les réservations',
    ],
    'pending' => [
        'heading' => 'À traiter',
        'open' => 'Ouvrir',
        'transmissions' => 'Transmissions à traiter',
        'certificates' => 'Attestations VGP à traiter',
        'deposits' => 'Cautions en attente',
        'purchase_orders' => 'Bons de commande manquants',
        'damages' => 'Dégâts à traiter',
        'overdue_sales' => 'Ventes en retard',
    ],
    'fleet' => [
        'heading' => 'État du parc',
    ],
    'vgp' => [
        'heading' => 'VGP à surveiller',
        'empty' => 'Aucune VGP à surveiller.',
        'expired' => 'Expirée le :date',
        'missing' => 'Non renseignée',
        'expires' => '{0} Expire le :date (aujourd\'hui)|{1} Expire le :date (dans :count jour)|[2,*] Expire le :date (dans :count jours)',
        'see_all' => 'Voir les machines soumises à VGP',
    ],
];
