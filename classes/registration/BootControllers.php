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
    protected $author;
    protected $plugin;
    protected $removeDiacritics = false;
    protected $useCurrencySymbol = true;

    protected function registerControllers()
    {
        // Init some vars
        list($this->author, $this->plugin) = explode('\\', strtolower(get_class()));
        $this->removeDiacritics = Config::get(
            sprintf('%s.%s::removeDiacritics', $this->author, $this->plugin),
            false
        );
        $this->useCurrencySymbol = Config::get(
            sprintf('%s.%s::useCurrencySymbol', $this->author, $this->plugin),
            true
        );

        $this->extendMallProduct();
    }

    protected function extendMallProduct()
    {
        /**
         * Extend controllers
         */
        \OFFLINE\Mall\Controllers\Products::extend(function ($controller) {

            // Init some vars
            $config = $controller->makeConfig(
                sprintf('$/%s/%s/config/product_import_export.yaml', $this->author, $this->plugin)
            );
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
                sprintf('%s.%s::lang.columns.price', $this->author, $this->plugin),
                [],
                $currentLocale
            );

            // Implement behavior if not already implemented
            if (!$controller->isClassExtendedWith('Backend.Behaviors.ImportExportController')) {
                $controller->implement[] = 'Backend.Behaviors.ImportExportController';
            }

            // Add views
            $partials_path = sprintf('$/%s/%s/partials', $this->author, $this->plugin);
            $controller->addViewPath($partials_path);
            $controller->vars['lang'] = $currentLocale;

            // Add currencies price columns
            $currencies = Currency::orderBy('is_default', 'DESC')->orderBy('sort_order', 'ASC')->get();
            $currencies->each(function (Currency $currency) use (
                $importList,
                $exportList,
                $priceTrad
            ) {
                $currencySymbol = $this->useCurrencySymbol ? $currency->symbol : $currency->code;
                $label = $this->removeDiacritics(sprintf('%s %s', $priceTrad, $currencySymbol));

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
                    $label = $this->removeDiacritics($category->name.' '.$currencySymbol);
                    $importList->columns['additional__'. $category->id .'__'.$currency->code] = [
                        'label' => $label
                    ];
                    $exportList->columns['additional__'. $category->id .'__'.$currency->code] = [
                        'label' => $label
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
                    $label = $this->removeDiacritics(
                        sprintf('%s %s', $priceTrad, strtolower($group->name).' '.$currency->symbol)
                    );
                    $importList->columns['group__' . $group->id .'__'.$currency->code] = [
                        'label' => $label,
                    ];
                    $exportList->columns['group__' . $group->id .'__'.$currency->code] = [
                        'label' => $label,
                    ];
                });
            });

            $config->import['list'] = $importList;
            $config->export['list'] = $exportList;

            // Set export file name from config
            $fileName = $config->export['fileName'];
            $newFileName = Config::get(sprintf('%s.%s::export.fileName', $this->author, $this->plugin, false));
            if ($newFileName) {
                $fileName = $newFileName;
            }
            // Append date to file name
            if (Config::get(sprintf('%s.%s::export.appendDate', $this->author, $this->plugin, false))) {
                $fileName .= (date(Config::get(sprintf('%s.%s::export.dateFormat', $this->author, $this->plugin), '_Y-m-d')));
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
        if ($this->removeDiacritics) {
            $label = strtr($label, $unwanted_array);
        }

        return $label;
    }
}
