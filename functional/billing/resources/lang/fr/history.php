<?php

return [
    'period_created' => 'Période facturable créée (:kind) du :start au :end, :days jour(s).',
    'sent' => 'Transmission :uuid transmise au logiciel de facturation (référence :external_ref).',
    'failed' => 'Transmission :uuid en échec : :reason',
    'unreachable' => 'Transmission :uuid : logiciel de facturation injoignable, nouvelle tentative dans :delay minute(s).',
    'retried' => 'Transmission :uuid relancée manuellement.',
    'exported' => 'Transmission :uuid incluse dans l\'export de secours n° :export.',
    'damage_billed' => 'Dégât n° :damage refacturé : :label, :amount € HT.',
    'damage_waived' => 'Dégât n° :damage classé non refacturé : :reason',
];
