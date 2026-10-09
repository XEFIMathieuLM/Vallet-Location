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
        'depart' => 'enregistrer la sortie',
        'return_in_good_state' => 'enregistrer le retour en état',
        'return_to_workshop' => 'enregistrer le retour à l\'atelier',
        'send_to_workshop' => 'envoyer à l\'atelier',
        'mark_out_of_order' => 'déclarer en panne',
        'make_available' => 'remettre disponible',
        'retire' => 'retirer du parc',
    ],

    'refusals' => [
        'illegal_transition' => 'Impossible de :transition : la machine est « :status ».',
    ],
];
