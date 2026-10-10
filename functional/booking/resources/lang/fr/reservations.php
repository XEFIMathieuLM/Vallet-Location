<?php

return [
    'navigation' => [
        'heading' => 'Réservations',
        'availability' => 'Disponibilités',
        'reservations' => 'Réservations',
    ],

    'fields' => [
        'reference' => 'Référence',
        'category' => 'Catégorie',
        'home_agency' => 'Agence de rattachement',
        'start_date' => 'Du',
        'end_date' => 'Au',
        'customer' => 'Client',
        'customer_name' => 'Nom du client',
        'customer_phone' => 'Téléphone',
        'customer_email' => 'E-mail',
        'status' => 'Statut',
        'agency' => 'Agence',
        'period' => 'Période',
        'machine' => 'Machine',
        'planned_end_date' => 'Fin prévue initialement',
        'created_by' => 'Créée par',
        'departed_at' => 'Sortie le',
        'returned_at' => 'Rentrée le',
    ],

    'list' => [
        'title' => 'Réservations',
        'all_statuses' => 'Tous les statuts',
        'in_conflict_only' => 'En conflit uniquement',
        'empty' => 'Aucune réservation ne correspond aux filtres.',
        'open' => 'Ouvrir',
    ],

    'detail' => [
        'title' => 'Réservation :reference',
        'in_conflict' => 'Réservation en conflit : :reason. Relogez le client ou annulez la réservation.',
        'cancel_confirmation' => 'Annuler cette réservation et libérer ses dates ?',
        'return_as' => 'Retour : :condition',
    ],

    'availability' => [
        'title' => 'Disponibilités',
        'all_categories' => 'Toutes les catégories',
        'all_agencies' => 'Toutes les agences',
        'invalid_period' => 'Période incohérente : la date de fin doit être postérieure ou égale à la date de début.',
        'no_machine' => 'Aucune machine disponible sur cette période.',
        'reserve' => 'Réserver',
    ],

    'form' => [
        'title' => 'Nouvelle réservation',
        'new_customer' => 'Nouveau client',
        'customer_search' => 'Rechercher un client',
        'choose_customer' => 'Choisir un client',
        'contact_required' => 'Au moins un moyen de contact (téléphone ou e-mail) est requis.',
        'confirm' => 'Confirmer la réservation',
        'back' => 'Retour aux disponibilités',
        'created' => 'Réservation de :reference enregistrée du :start au :end.',
    ],

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

    'transitions' => [
        'depart' => 'Enregistrer la sortie',
        'return' => 'Enregistrer le retour',
        'cancel' => 'Annuler la réservation',
    ],

    'return_conditions' => [
        'good_state' => 'En état',
        'workshop' => 'À l\'atelier',
    ],

    'late_returns' => [
        'flagged' => '{0} Aucune machine en retard.|{1} :count machine en retard vérifiée.|[2,*] :count machines en retard vérifiées.',
    ],

    'refusals' => [
        'illegal_transition' => 'Action « :transition » impossible : la réservation est « :status ».',
        'departure_before_start' => 'Sortie impossible avant la date de début de la réservation (:date).',
        'departure_machine_status' => 'Sortie impossible : la machine est « :status ».',
        'departure_vgp_missing' => 'Sortie impossible : la VGP de la machine n\'est pas renseignée.',
        'departure_vgp_expires' => 'Sortie impossible : la VGP de la machine expire le :date, avant la fin de la réservation.',
        'overlap' => 'Machine déjà réservée du :start au :end par l\'agence :agency.',
        'concurrent_overlap' => 'Machine réservée sur ces dates par une autre agence à l\'instant. Relancez la recherche de disponibilité.',
        'start_in_the_past' => 'La date de début ne peut pas être dans le passé.',
        'end_before_start' => 'La date de fin doit être postérieure ou égale à la date de début.',
        'machine_status' => 'Machine non réservable : elle est « :status ».',
        'vgp_missing' => 'Machine non réservable : sa VGP n\'est pas renseignée.',
        'vgp_expires' => 'Machine non réservable : sa VGP expire le :date, avant la fin de la période.',
        'machine_not_returned' => 'Machine non réservable : elle n\'est pas encore rentrée de sa location précédente.',
    ],
];
