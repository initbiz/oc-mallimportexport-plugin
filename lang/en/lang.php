<?php

return [

    'plugin' => [
        'name' => 'Mall import/export',
        'description' => 'Import/Export for the Offline.Mall plugin',
        'author' => 'Hounddd'
    ],

    'menus' => [
        'importexport' => 'Import or export',
        'import' => 'Import data',
        'export' => 'Export data',
    ],

    'import' => [
        'title' => 'Updating shop’s data',
        'hint_title' => 'Help with importing data into the shop',
        'hint_content_left' => '<p>Export your data in CSV format (from Microsoft Excel, ' .
                                'File > Save as, then choose the CSV UTF-8 format).</p>',
        'hint_content_right' => '<p>The following data fields are required:</p><ul>:fields</ul>',
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
        'price' => 'Price',
        'published' => 'Published',
        'stock' => 'Stock',
        'user_defined_id' => 'Reference',
        'weight' => 'Weight (g)',
        'link' => 'Link to product',
    ],

    'ux' => [
        'export_button' => 'Export data',
        'import_button' => 'Update data',
        'export_links' => 'Export link to product',
        'return_list' => 'Back to the product list',
    ],
];
