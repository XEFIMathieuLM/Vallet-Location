<?php

return [
    'illegal_sale_transition' => 'Action « :transition » impossible : la vente est « :status ».',
    'illegal_offer_transition' => 'Action « :transition » impossible : l\'offre est « :status ».',
    'non_positive_price' => 'Le prix doit être supérieur à zéro.',
    'machine_already_for_sale' => 'Cette machine a déjà une vente « :status » : :price € HT, ouverte par l\'agence :agency le :date.',
    'concurrent_listing' => 'Une autre agence a mis cette machine en vente au même moment. Rechargez la page pour voir sa vente.',
    'machine_already_sold' => 'La machine :reference a déjà été vendue : elle ne peut plus être mise en vente.',
    'asking_price_locked' => 'Le prix demandé n\'est plus modifiable : la vente est « :status ».',
    'description_locked' => 'Le descriptif n\'est plus modifiable : la vente est « :status ».',
    'offer_sale_not_listed' => 'Impossible d\'enregistrer une offre : la vente est « :status ».',
    'offer_non_positive_amount' => 'Le montant de l\'offre doit être supérieur à zéro.',
    'offer_in_the_future' => 'La date de l\'offre ne peut pas être dans le futur.',
    'concurrent_acceptance' => 'Une autre offre de cette vente a été acceptée au même moment. Rechargez la page.',
    'handover_date_in_the_past' => 'La date de remise prévue (:date) est dans le passé.',
    'handover_conflicts_with_reservation' => 'Remise prévue le :date impossible : la machine est louée du :start au :end (agence :agency, client :customer).',
    'machine_reserved_for_sale' => 'Machine vendue sous réserve, remise prévue le :date : elle ne peut pas être louée jusqu\'à cette date ni au-delà.',
    'reason_required' => 'Un motif est obligatoire.',
];
