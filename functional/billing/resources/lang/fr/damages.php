<?php

return [
    'bill' => 'Refacturer',
    'waive' => 'Ne pas refacturer',
    'amount' => 'Montant HT (€)',
    'label' => 'Libellé de la réparation',
    'waiver_reason' => 'Motif',
    'confirm_bill' => 'Valider la refacturation',
    'confirm_waive' => 'Valider',
    'cancel' => 'Annuler',
    'amount_format' => 'Le montant doit être un nombre positif, avec au plus deux décimales (ex. 450 ou 450,50).',
    'section' => [
        'title' => 'Dégâts',
        'damage' => ':view : :comment',
        'billed' => ':label, :amount € HT',
        'waived' => 'Motif : :reason (par :author le :date)',
    ],
    'refusals' => [
        'already_settled' => 'Ce dégât est déjà réglé : toute correction se fait par un avoir dans le logiciel de facturation.',
        'amount_required' => 'Le montant doit être strictement positif. Pour ne rien refacturer, choisissez « Ne pas refacturer » avec un motif.',
        'label_required' => 'Le libellé de la réparation est obligatoire.',
        'waiver_reason_required' => 'Le motif est obligatoire pour ne pas refacturer un dégât.',
    ],
];
