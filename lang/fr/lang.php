<?php

return [

    'plugin' => [
        'name' => 'Mall import/export',
        'description' => 'Simple Import/Export pour le plugin Offline.Mall',
        'author' => 'Hounddd'
    ],

    'menus' => [
        'importexport' => 'Import ou export',
        'import' => 'Import des données',
        'export' => 'Export des données',
    ],

    'import' => [
        'title' => 'Mettre à jour les données à la boutique',
        'hint_title' => 'Aide pour l’import des données dans la boutique',
        'hint_content_left' => '<p>Exporter vos données dans un fichier au format CSV (depuis Microsoft Excel, '
                                .'Fichier > Enregistrer sous, puis choisir le format CSV UTF-8).</p>',
        'hint_content_right' => '<p>Les champs de données suivants sont requis:</p><ul>:fields</ul>',
        'errors' => [
            'emptyline' => 'La ligne est vide',
            'notaproduct' => 'Le produit :ref ne correspond à aucun produit ou variante.',
            'forref' => 'Pour le produit :ref ',
            'notanumber' => ', la colonne :type (:price) n’est pas valide',
            'notacurrency' => ', le code de la monnaie (:code) est inconnu',
        ],
    ],

    'export' => [
        'title' => 'Exporter des données de la boutique',
    ],

    'columns' => [
        'allow_out_of_stock_purchases' => 'Vente en rupture de stock',
        'price' => 'Prix',
        'published' => 'Publié',
        'stock' => 'Stock',
        'user_defined_id' => 'Référence',
        'weight' => 'Poids (g)',
        'link' => 'Lien vers le produit',
        'admin_link' => 'Editer le produit',
    ],

    'ux' => [
        'export_button' => 'Exporter les données',
        'import_button' => 'Mettre à jour les données',
        'only_variants' => 'Uniquement les variantes',
        'only_variants_comment' => 'pour les produits avec variantes, ne conserver que les variantes',
        'export_links' => 'Exporter un lien vers le produit',
        'export_links_comment' => 'un lien vers la page publique du produit est ajouté',
        'export_admin_links' => 'Exporter le lien vers le backend du produit',
        'export_admin_links_comment' => 'un lien vers la page de modification du produit est ajouté',
        'return_list' => 'Retourner à la liste des produits',
    ],

    'permissions' => [
        'import' => 'Accès à l’import des données de la boutique.',
        'export' => 'Accès à l’export des données de la boutique.',
    ],
];
