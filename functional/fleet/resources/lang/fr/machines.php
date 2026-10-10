<?php

return [
    'navigation' => [
        'fleet' => 'Parc',
    ],

    'fields' => [
        'reference' => 'Référence',
        'category' => 'Catégorie',
        'agency' => 'Agence de rattachement',
        'status' => 'Statut',
        'is_subject_to_vgp' => 'Soumise à VGP',
        'vgp_due_date' => 'Échéance VGP',
    ],

    'index' => [
        'title' => 'Parc de machines',
        'create' => 'Nouvelle machine',
        'all' => 'Toutes',
        'actions' => 'Actions',
        'edit' => 'Modifier',
        'vgp_missing' => 'Non renseignée',
        'actions_for' => 'Actions pour :reference',
        'empty' => 'Aucune machine à afficher.',
        'empty_help' => 'Aucune machine ne correspond aux filtres. Retirez des filtres, ou importez le parc s\'il est encore vide.',
        'status_changed' => 'Machine :reference : statut « :status » enregistré.',
        'retire_heading' => 'Retirer la machine du parc ?',
        'retire_confirmation' => 'La machine :reference ne pourra plus être réservée ni changer de statut. Cette action est définitive.',
        'keep_machine' => 'Garder la machine',
        'confirm_retirement' => 'Retirer du parc',
    ],

    'form' => [
        'create_title' => 'Nouvelle machine',
        'edit_title' => 'Modifier la machine',
        'choose' => 'Choisir…',
        'vgp_forced_by_category' => 'Toujours coché pour une catégorie soumise à VGP (nacelles).',
        'save' => 'Enregistrer',
        'back' => 'Retour au parc',
        'saved' => 'Machine :reference enregistrée.',
    ],

    'status' => [
        'available' => 'Disponible',
        'rented_out' => 'Sortie',
        'workshop' => 'Atelier',
        'out_of_order' => 'En panne',
        'retired' => 'Retirée du parc',
    ],

    'transitions' => [
        'depart' => 'Enregistrer la sortie',
        'return_in_good_state' => 'Enregistrer le retour en état',
        'return_to_workshop' => 'Enregistrer le retour à l\'atelier',
        'send_to_workshop' => 'Envoyer à l\'atelier',
        'mark_out_of_order' => 'Déclarer en panne',
        'make_available' => 'Remettre disponible',
        'retire' => 'Retirer du parc',
    ],

    'refusals' => [
        'duplicate_reference' => 'La référence :reference existe déjà dans le parc. Saisissez une autre référence, ou modifiez la machine existante depuis le parc.',
        'retirement_with_reservations' => '{1} Retrait de :reference impossible : la machine a une réservation confirmée ou en cours.|[2,*] Retrait de :reference impossible : la machine a :count réservations confirmées ou en cours.',
        'illegal_transition' => 'Action « :transition » impossible : la machine est « :status ».',
    ],

    'import' => [
        'title' => 'Import du parc',
        'file' => 'Fichier CSV (séparateur ;) ou XLSX',
        'format_help' => 'Colonnes : reference, categorie, agence, soumise_vgp (oui / non), echeance_vgp (JJ/MM/AAAA). Les machines existantes ne sont jamais modifiées.',
        'submit' => 'Importer',
        'created' => '{0} Aucune machine créée.|{1} 1 machine créée.|[2,*] :count machines créées.',
        'rejected_lines' => 'Lignes rejetées',
        'line' => 'Ligne',
        'reason' => 'Motif',
        'rejections' => [
            'missing_reference' => 'Référence vide',
            'existing_reference' => 'Référence déjà présente dans le parc',
            'duplicate_in_file' => 'Référence en double dans le fichier',
            'unknown_category' => 'Catégorie inconnue : :category',
            'unknown_agency' => 'Agence inconnue : :agency',
            'unreadable_date' => 'Date d\'échéance VGP illisible : :date',
        ],
    ],
];
