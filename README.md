# Mall Import/Export

This plugin offers the possibility to import or export data into the [OFFLINE.Mall](https://github.com/OFFLINE-GmbH/oc-mall-plugin) plugin.  

***
⚠ **Please note that it does NOT allow you to add products but to update existing products**.

***

## What you can import or export

 - publication status
 - stocks
 - authorisation for sales out of stock
 - weight
 - prices, additional prices and group prices, in all currencies.
 - [and whatever you decide](#mallimportexportownfields)


## How it works
The plugin uses the CSV file [import/export features of WinterCMS](https://wintercms.com/docs/backend/import-export).

With default options, exporting data will give you a CSV file named `Products_export_2021-09-23.csv`.  

### Columns naming 
The plugin use currencies symbols to append same to the column name.
 - The prices columns will be named "Price $", "Price €", ... 
 - The customers prices columns will be named "Price *pricename* $", "Price *pricename* €", ... 
    <br />ex for a "Vip" customer price : "Price vip $", "Price vip €", ... 
 - The defined price categories use their own names "*Price category name* $", "*Price category name* €", ...
    <br />ex for a "Old price" price category : "Old price $", "Old price €", ... 

You can define your own filename, date or column's names from [config](#mallimportexportconfig).

### <a name="mallimportexportownfields"></a>Add your own columns
You can listen for `hounddd.mallimportexport.config.update` event to add your own column definition.

#### Example to add the product's name to export fields, at the second position
```php
/**
 * Extend products controller export columns definition
 */
Event::listen('hounddd.mallimportexport.config.update', function ($controller, $config) {
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

## Installation
*Let assume you're in the root of your wintercms installation*

### Using composer
Just run this command
```bash
composer require hounddd/wn-mallimportexport
```

### Cloning repo
Clone this repo into your winter plugins folder.

```bash
cd plugins
mkdir hounddd && cd hounddd
git clone https://github.com/Hounddd/wn-mallimportexport mallimportexport
```
## <a name="mallimportexportconfig"></a>Configuring
### Import/export behaviors
You can configure plugin behaviors with a file `\config\hounddd\mallimportexport\config.php`

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

    'useCurrencySymbol' => true,            // Use currency symbol or code, default true
    'removeDiacritics' => false,            // Remove diactritcis chars in additional prices column labels, default false
];
```
 - **useCurrencySymbol** : (default true) Defined if additionnal price columns must use currency symbol ou currency code.  
 - **removeDiacritics** : (default false) Defined if diacritics characters must be removed from prices and additionnal prices column labels.  

For the other columns use the translations (see below).  
This allows you to bypass encoding problems with import or export files.  

See [PHP date format](https://www.php.net/manual/datetime.format.php) for dateFormat accepted values.  
Remember that this is for the name of a file, respect the conventions to avoid trouble.

### Csv file column's names
You can change the column names to match your needs, simply add a `\lang\XX\hounddd\mallimportexport\lang.php` (where XX is your locale) to your site.
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

***
Make awesome sites with [WinterCMS](https://wintercms.com)!