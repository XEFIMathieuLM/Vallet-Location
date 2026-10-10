<?php

return [
    'illegal_transition' => 'Impossible : la caution est « :status ».',
    'customer_type_missing' => 'Le type de client (particulier ou professionnel) doit être renseigné avant la sortie.',
    'not_collected' => 'La caution de :amount € doit être encaissée avant la sortie.',
    'reservation_not_confirmed' => 'Une caution ne s\'encaisse que sur une réservation confirmée.',
    'customer_not_individual' => 'Seul un client particulier verse une caution.',
    'already_collected' => 'La caution de cette réservation est déjà encaissée.',
    'reference_required' => 'La référence est obligatoire pour un paiement par :method.',
    'reason_required' => 'Le motif de la correction est obligatoire.',
    'no_damage_not_confirmed' => 'Confirmez que la comparaison départ / retour a été faite et qu\'aucun dégât n\'a été constaté.',
    'damages_to_settle' => '{1} Un dégât est à régler avant de rendre la caution.|[2,*] :count dégâts sont à régler avant de rendre la caution.',
    'not_refundable' => 'La caution ne peut pas être restituée : elle est « :status ».',
    'not_settleable' => 'La caution ne peut pas être soldée : elle est « :status ».',
    'already_closed' => 'La caution est déjà restituée ou soldée.',
    'invalid_amount' => 'Le montant de caution doit être strictement positif.',
];
