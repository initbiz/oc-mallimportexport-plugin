# Mall Import/Export

Forked from [Hounddd/wn-mallimportexport-plugin](https://github.com/hounddd/wn-mallimportexport-plugin).

It was adjusted to match newest October CMS Mall installation and to handle over 40k variants.

It's not a production ready plugin, PRs are welcomed.

This plugin lets you export data from [OFFLINE.Mall](https://github.com/OFFLINE-GmbH/oc-mall-plugin) plugin.

Importing wasn't tested.

## What you can import or export

-   publication status
-   stocks
-   authorisation for sales out of stock
-   weight
-   prices, additional prices and group prices, in all currencies.
-   [and whatever you decide](#mallimportexportownfields)

### Columns naming

The plugin use currencies symbols to append same to the column name.

-   The prices columns will be named "Price $", "Price €", ...
-   The customers prices columns will be named "Price _pricename_ $", "Price _pricename_ €", ...
    <br />ex for a "Vip" customer price : "Price vip $", "Price vip €", ...
-   The defined price categories use their own names "_Price category name_ $", "_Price category name_ €", ...
    <br />ex for a "Old price" price category : "Old price $", "Old price €", ...

You can define your own filename, date or column's names from [config](#mallimportexportconfig).

### <a name="mallimportexportownfields"></a>Add your own columns

You can listen for `initbiz.mallimportexport.config.update` event to add your own column definition.

#### Example to add the product's name to export fields, at the second position

```php
/**
 * Extend products controller export columns definition
 */
Event::listen('initbiz.mallimportexport.config.update', function ($controller, $config) {
    $exportColumns = $config->export['list']->columns;
    $position = 1;

    $config->export['list']->columns =
        array_slice($exportColumns, 0, $position, true) +
        [
            'name' => [
                'label' => 'offline.mall::lang.product.name'
            ]
        ] +
        array_slice($exportColumns, $position, count($exportColumns) - 1, true);
});
```

### Integration

If the version of your OFFLINE.Mall plugin includes event hooks for toolbars (>= 1.14.4), you will find the import and export buttons at the top of the product list, otherwise an entry will be added to the side menu of the plugin.
Buttons are available according to the user rights.

### Import/export behaviors

You can configure plugin behaviors with a file `\config\initbiz\mallimportexport\config.php`

```php
<?php

return [
    'import' => [
    ],

    'export' => [
        'fileName' => 'Export_produits',    // New export filename, default "Products_export"
        'appendDate' => true,               // Append date to filename, default true
        'dateFormat' => '_Y-m-d-z',         // How to format appended date, default '_Y-m-d'
    ],

    'useCurrencySymbol' => true,            // Use currency symbol (true), code (false), or nothing (null), default true
    'removeDiacritics' => false,            // Remove diactritcis chars in additional prices column labels, default false
];
```

-   **useCurrencySymbol** : (default true) Defined if additionnal price columns must use currency symbol ou currency code.
-   **removeDiacritics** : (default false) Defined if diacritics characters must be removed from prices and additionnal prices column labels.

For the other columns use the translations (see below).
This allows you to bypass encoding problems with import or export files.

See [PHP date format](https://www.php.net/manual/datetime.format.php) for dateFormat accepted values.
Remember that this is for the name of a file, respect the conventions to avoid trouble.

### Csv file column's names

You can change the column names to match your needs, simply add a `\lang\XX\initbiz\mallimportexport\lang.php` (where XX is your locale) to your site.

```php
<?php
    return [

        'columns' => [
            'allow_out_of_stock_purchases' => 'Vente hors stock',
            'price'                        => 'Prix en',
            'published'                    => 'Publié',
            'stock'                        => 'Stock',
            'user_defined_id'              => 'Référence',
            'weight'                       => 'Poids en g',
        ],

    ];

```
