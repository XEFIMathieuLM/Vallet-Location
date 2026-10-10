<?php

return [
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
        'illegal_transition' => 'Action « :transition » impossible : la machine est « :status ».',
    ],
];
