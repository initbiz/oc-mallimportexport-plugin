<?php

namespace Initbiz\MallImportExport\EventHandlers;

use App;
use Lang;
use Event;
use Config;
use OFFLINE\Mall\Models\Currency;
use Backend\Models\UserPreference;
use October\Rain\Events\Dispatcher;
use OFFLINE\Mall\Controllers\Products;
use OFFLINE\Mall\Models\CustomerGroup;
use OFFLINE\Mall\Models\PriceCategory;

class OfflineMallHandler
{
    public function subscribe($event)
    {
        if (App::runningInBackend()) {
            $this->extendMallProduct($event);
        }
    }

    protected function extendMallProduct(Dispatcher $event)
    {
        Products::extend(function ($controller) {
            if (!$controller->isClassExtendedWith('Backend.Behaviors.ImportExportController')) {
                $controller->implement[] = 'Backend.Behaviors.ImportExportController';
            }

            $config = $controller->makeConfig('$/initbiz/mallimportexport/config/product_import_export.yaml');

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
            $userPreferences = UserPreference::forUser()->get('backend::backend.preferences');
            $currentLocale = array_get($userPreferences, 'locale', App::getLocale());

            $priceTrad = Lang::get('initbiz.mallimportexport::lang.columns.price', [], $currentLocale);

            // Init currency symbol
            $currency = Currency::activeCurrency();
            $currencySymbol = $currency->symbol;

            // Add views
            $controller->addViewPath('initbiz/mallimportexport/partials');
            $controller->vars['lang'] = $currentLocale;

            // Add currencies price columns
            $currencies = Currency::orderBy('is_default', 'DESC')->orderBy('sort_order', 'ASC')->get();
            $currencies->each(function (Currency $currency) use ($importList, $exportList, $priceTrad, $currencySymbol) {
                $label = $this->removeDiacritics(sprintf('%s %s', $priceTrad, $currency->symbol));

                $importList->columns['price__' . $currency->code] = [
                    'label' => $label
                ];

                $exportList->columns['price__' . $currency->code] = [
                    'label' => $label
                ];

                // Add additional price categories
                $additionalPriceCategories = PriceCategory::orderBy('sort_order', 'ASC')->get();
                $additionalPriceCategories->each(function (PriceCategory $category) use (
                    $currency,
                    $importList,
                    $exportList,
                    $currencySymbol
                ) {
                    $label = $this->removeDiacritics($category->name . ' ' . $currencySymbol);
                    $importList->columns['additional__' . $category->id . '__' . $currency->code] = [
                        'label' => $label
                    ];
                    $exportList->columns['additional__' . $category->id . '__' . $currency->code] = [
                        'label' => $label
                    ];
                });

                // Add customer's group price columns
                $customerGroups = CustomerGroup::orderBy('sort_order', 'ASC')->get();
                $customerGroups->each(function (CustomerGroup $group) use (
                    $currency,
                    $importList,
                    $exportList,
                    $priceTrad
                ) {
                    $label = $this->removeDiacritics(
                        sprintf('%s %s', $priceTrad, strtolower($group->name) . ' ' . $currency->symbol)
                    );
                    $importList->columns['group__' . $group->id . '__' . $currency->code] = [
                        'label' => $label,
                    ];
                    $exportList->columns['group__' . $group->id . '__' . $currency->code] = [
                        'label' => $label,
                    ];
                });
            });

            $config->import['list'] = $importList;
            $config->export['list'] = $exportList;

            // Set export file name from config
            $fileName = $config->export['fileName'];
            $newFileName = Config::get('initbiz.mallimportexport::export.fileName', false);
            if ($newFileName) {
                $fileName = $newFileName;
            }

            // Append date to file name
            $appendDate = Config::get('initbiz.mallimportexport::export.appendDate', false);
            if ($appendDate) {
                $dateFormat = Config::get('initbiz.mallimportexport::export.dateFormat',  '_Y-m-d');
                $fileName .= (date($dateFormat));
            }

            $config->export['fileName'] = $fileName . '.csv';

            if (!isset($controller->importExportConfig)) {
                $controller->addDynamicProperty('importExportConfig', $config);
            }

            // Allow config to be extendable with events
            Event::fire('initbiz.mallimportexport.config.update', [$controller, $config]);
        });
    }

    /**
     * Remove diacritics from a string
     *
     * @param string $label
     * @return string
     */
    protected function removeDiacritics($label)
    {
        $unwanted_array = array(
            'Š'=>'S', 'š'=>'s',
            'Ž'=>'Z', 'ž'=>'z',
            'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A',
            'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a',
            'Ç'=>'C',
            'ç'=>'c',
            'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E',
            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e',
            'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'İ'=>'I',
            'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ı'=>'i',
            'Ğ'=>'G',
            'ğ'=>'g',
            'Ñ'=>'N',
            'ñ'=>'n',
            'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ő'=>'O',
            'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ő'=>'o',
            'ð'=>'o',
            'Ş'=>'S',
            'ş'=>'s',
            'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ű'=>'U',
            'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ű'=>'u',
            'Ý'=>'Y', 'Ÿ'=>'Y',
            'ý'=>'y', 'ÿ'=>'y',
            'Þ'=>'B',
            'þ'=>'b',
            'ß'=>'ss',
        );

        $label = strtr($label, $unwanted_array);

        return $label;
    }
}
