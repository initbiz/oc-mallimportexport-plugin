<?php

return [

    'plugin' => [
        'name' => 'Mall import/export',
        'description' => 'Import/Export for the Offline.Mall plugin',
        'author' => 'Initbiz'
    ],

    'menus' => [
        'importexport' => 'Import or export',
        'import' => 'Import data',
        'export' => 'Export data',
    ],

    'import' => [
        'title' => 'Updating shop’s data',
        'errors' => [
            'emptyline' => 'The line is empty',
            'notaproduct' => 'The product :ref does not correspond to any product or variant.',
            'forref' => 'For the product :ref ',
            'notanumber' => ', the column :type (:price) is not valid',
            'notacurrency' => ', the currency code (:code) is unknown',
        ],
    ],

    'export' => [
        'title' => 'Exporting shop’s data',
    ],

    'columns' => [
        'allow_out_of_stock_purchases' => 'Sale out of stock',
        'name' => 'Name',
        'description' => 'Description',
        'price' => 'Price',
        'published' => 'Published',
        'stock' => 'Stock',
        'user_defined_id' => 'Reference',
        'weight' => 'Weight (g)',
        'link' => 'Link to product',
        'admin_link' => 'Edit product',
    ],

    'ux' => [
        'export_button' => 'Export data',
        'import_button' => 'Update data',
        'only_variants' => 'Only product variants',
        'only_variants_comment' => 'for products with variants, keep only the variants',
        'export_links' => 'Export link to product',
        'export_links_comment' => 'a link to the public product page is added',
        'export_admin_links' => 'Export link to product’s backend',
        'export_admin_links_comment' => 'a link to the edit product page is added',
        'return_list' => 'Back to the product list',
        'export_success_message' => 'File is being generated. Refresh the page in a minute to download it.',
        'generated_file_label' => 'Most recently generated file:',
        'refresh_page' => 'Refresh page',
        'export_ongoing' => 'Exporting records is in progress. Wait for the file to be generated.',
    ],
];
