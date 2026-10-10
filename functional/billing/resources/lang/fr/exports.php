<?php

return [
    'title' => 'Exports de secours',
    'file_name' => 'export-facturation-:timestamp.csv',
    'intro' => 'À utiliser quand l\'envoi automatique ne fonctionne pas : le fichier reprend tout ce qui est en attente ou en échec, et ces éléments ne seront plus envoyés automatiquement.',
    'create' => 'Produire un export',
    'confirm' => 'Tous les éléments en attente ou en échec seront inclus dans le fichier et ne seront plus envoyés automatiquement. Continuer ?',
    'empty' => 'Aucun export produit. Un export n\'est utile que si l\'envoi automatique ne fonctionne pas.',
    'created_toast' => 'Export produit : :count ligne. Téléchargez-le pour l\'importer dans le logiciel de facturation.|Export produit : :count lignes. Téléchargez-le pour l\'importer dans le logiciel de facturation.',
    'date' => 'Date',
    'author' => 'Auteur',
    'lines' => 'Lignes',
    'line_count' => ':count ligne|:count lignes',
    'download' => 'Télécharger',
    'refusals' => [
        'nothing_to_export' => 'Aucun élément en attente ou en échec : il n\'y a rien à exporter.',
    ],
];
