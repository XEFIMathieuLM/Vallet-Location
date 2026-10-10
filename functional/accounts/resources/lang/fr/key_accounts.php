<?php

return [
    'navigation' => [
        'heading' => 'Grands comptes',
        'key_accounts' => 'Grands comptes',
    ],
    'badge' => [
        'label' => 'Grand compte',
        'description' => 'Tarif négocié appliqué par la facturation — bon de commande exigé avant la sortie.',
    ],
    'screen' => [
        'title' => 'Grands comptes',
        'intro' => 'Un grand compte est un client professionnel dont le tarif négocié est appliqué par le logiciel de facturation. Un bon de commande est exigé avant chaque sortie.',
        'empty_heading' => 'Aucun grand compte',
        'empty' => 'Recherchez un client professionnel ci-dessous pour le désigner.',
        'customer' => 'Client',
        'billing_ref' => 'Identifiant de facturation',
        'designated' => 'Désignation',
        'designated_by' => 'par :author le :date',
        'revoke' => 'Retirer la désignation',
        'revoke_heading' => 'Retirer la désignation de :customer ?',
        'revoke_help' => 'Le bon de commande ne sera plus exigé pour ses réservations ; les numéros déjà saisis restent et sont transmis.',
        'cancel' => 'Annuler',
        'designate_heading' => 'Désigner un grand compte',
        'search' => 'Rechercher un client professionnel',
        'no_candidate' => 'Aucun client professionnel trouvé',
        'no_candidate_help' => 'Seuls les clients professionnels qui ne sont pas déjà grands comptes apparaissent.',
        'no_billing_ref' => 'Identifiant de facturation non renseigné',
        'designate' => 'Désigner grand compte',
        'designated_toast' => ':customer est désormais grand compte.',
        'revoked' => ':customer n\'est plus grand compte.',
    ],
];
