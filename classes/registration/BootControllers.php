<?php namespace Hounddd\MallImportExport\Classes\Registration;

use App;
use Config;
use Event;
use Lang;
use Backend\Models\UserPreference;
use OFFLINE\Mall\Models\Currency;
use OFFLINE\Mall\Models\CustomerGroup;
use OFFLINE\Mall\Models\PriceCategory;

trait BootControllers
{
    protected function registerControllers()
    {
        $this->extendMallProduct();
    }

    protected function extendMallProduct()
    {
        /**
         * Extend controllers
         */
        \OFFLINE\Mall\Controllers\Products::extend(function ($controller) {

            // Init some vars
            list($author, $plugin) = explode('\\', strtolower(get_class()));
            $config = $controller->makeConfig(sprintf('$/%s/%s/config/product_import_export.yaml', $author, $plugin));
            $importList = $controller->makeConfig($config->import['list']);
            $exportList = $controller->makeConfig($config->export['list']);

            /*
            |--------------------------------------------------------------------------
            | Get default "price" translation according to user choice
            |--------------------------------------------------------------------------
            |
            | Direct use of `Lang::get()` does not take into consideration the user
            | preference choice and as we have to add a string to the column labels,
            | it's not possible to use a translation key directly in the column label.
            |
            | It is therefore necessary to force the translation by specifying the
            | language to use.
            |
            */
            $currentLocale = array_get(
                UserPreference::forUser()->get('backend::backend.preferences'),
                'locale',
                App::getLocale()
            );
            $priceTrad = Lang::get(
                sprintf('%s.%s::lang.columns.price', $author, $plugin),
                [],
                $currentLocale
            );

            // Implement behavior if not already implemented
            if (!$controller->isClassExtendedWith('Backend.Behaviors.ImportExportController')) {
                $controller->implement[] = 'Backend.Behaviors.ImportExportController';
            }

            // Add views
            $partials_path = sprintf('$/%s/%s/partials', $author, $plugin);
            $controller->addViewPath($partials_path);
            $controller->vars['lang'] = $currentLocale;

            // Add currencies price columns
            $currencies = Currency::orderBy('is_default', 'DESC')->orderBy('sort_order', 'ASC')->get();
            $currencies->each(function (Currency $currency) use (
                $importList,
                $exportList,
                $priceTrad
            ) {
                $importList->columns['price__' . $currency->code] = [
                    'label' => sprintf('%s %s', $priceTrad, $currency->symbol)
                ];
                $exportList->columns['price__' . $currency->code] = [
                    'label' => sprintf('%s %s', $priceTrad, $currency->symbol)
                ];

                // Add additional price categories
                $additionalPriceCategories = PriceCategory::orderBy('sort_order', 'ASC')->get();
                $additionalPriceCategories->each(function (PriceCategory $category) use (
                    $currency,
                    $importList,
                    $exportList
                ) {
                    $importList->columns['additional__'. $category->id .'__'.$currency->code] = [
                        'label' => $category->name.' '.$currency->symbol
                    ];
                    $exportList->columns['additional__'. $category->id .'__'.$currency->code] = [
                        'label' => $category->name.' '.$currency->symbol
                    ];
                });

                // Add custormer's group price columns
                $customerGroups = CustomerGroup::orderBy('sort_order', 'ASC')->get();
                $customerGroups->each(function (CustomerGroup $group) use (
                    $currency,
                    $importList,
                    $exportList,
                    $priceTrad
                ) {
                    $importList->columns['group__' . $group->id .'__'.$currency->code] = [
                        'label' => sprintf('%s %s', $priceTrad, strtolower($group->name).' '.$currency->symbol),
                    ];
                    $exportList->columns['group__' . $group->id .'__'.$currency->code] = [
                        'label' => sprintf('%s %s', $priceTrad, strtolower($group->name).' '.$currency->symbol),
                    ];
                });
            });

            $config->import['list'] = $importList;
            $config->export['list'] = $exportList;

            // Set export file name from config
            $fileName = $config->export['fileName'];
            $newFileName = Config::get(sprintf('%s.%s::export.fileName', $author, $plugin, false));
            if ($newFileName) {
                $fileName = $newFileName;
            }
            // Append date to file name
            if (Config::get(sprintf('%s.%s::export.appendDate', $author, $plugin, false))) {
                $fileName .= (date(Config::get(sprintf('%s.%s::export.dateFormat', $author, $plugin), '_Y-m-d')));
            }

            $config->export['fileName'] = $fileName .'.csv';

            // Allow config to be extendable with events
            Event::fire('hounddd.mallimportexport.config.update', [$controller, $config]);

            // Define property if not already defined
            if (!isset($controller->importExportConfig)) {
                $controller->addDynamicProperty('importExportConfig', $config);
            }
        });
    }
}
