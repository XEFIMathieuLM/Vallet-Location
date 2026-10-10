<?php

return [
    'transitions' => [
        'await_email' => 'mettre en attente d\'e-mail',
        'queue' => 'mettre en file d\'envoi',
        'send' => 'marquer envoyée',
        'fail' => 'marquer en échec',
        'hand_deliver' => 'enregistrer la remise en main propre de',
    ],
    'refusals' => [
        'illegal_transition' => 'Impossible de :transition l\'attestation VGP : elle est à l\'état « :status ».',
    ],
];
