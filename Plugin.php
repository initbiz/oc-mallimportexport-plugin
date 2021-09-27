<?php namespace Hounddd\MallImportExport;

use App;
use Auth;
use Backend;
use BackendAuth;
use Config;
use Event;
use Lang;
use Yaml;
use Backend\Models\UserPreference;
use System\Classes\PluginBase;
use System\Models\PluginVersion;
use OFFLINE\Mall\Models\Currency;
use OFFLINE\Mall\Models\CustomerGroup;
use OFFLINE\Mall\Models\PriceCategory;

/**
 * MallImportExport Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = ['Offline.Mall'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'hounddd.mallimportexport::lang.plugin.name',
            'description' => 'hounddd.mallimportexport::lang.plugin.description',
            'author'      => 'Hounddd',
            'icon'        => 'icon-retweet',
            'homepage'    => 'https://github.com/Hounddd/wn-mallimportexport-plugin',
        ];
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        $mallVersion = PluginVersion::getVersion('OFFLINE.Mall');

        /**
         * Extend OFFLINE.Mall plugin's menu or products toolbar
         */
        if ($mallVersion >= '1.14.4') {
            /**
             * Extend products view toolbar
             */
            Event::listen('offline.mall.extendProductsToolbar', function ($controller) {
                $buttons = [];
                $currentUser = BackendAuth::getUser();

                if ($currentUser->hasAccess('hounddd.mallimportexport.*')) {
                    $buttons[] = '<span class="p-x"></span>';
                }
                if ($currentUser->hasPermission('hounddd.mallimportexport.import')) {
                    $buttons[] = '<a href="'. Backend::url('offline/mall/products/import') .'" '
                        .'class="btn btn-info oc-icon-cloud-download">'
                        .trans('hounddd.mallimportexport::lang.menus.import') .'</a>';
                }
                if ($currentUser->hasPermission('hounddd.mallimportexport.export')) {
                    $buttons[] = '<a href="'. Backend::url('offline/mall/products/export') .'" '
                        .'class="btn btn-info oc-icon-cloud-upload">'
                        .trans('hounddd.mallimportexport::lang.menus.export') .'</a>';
                }
                return implode('', $buttons);
            });
        } else {
            /**
             * Extend backend menus
             */
            Event::listen('backend.menu.extendItems', function ($manager) {
                $buttons = [];
                $currentUser = BackendAuth::getUser();

                if ($currentUser->hasPermission('hounddd.mallimportexport.import')
                    && $currentUser->hasPermission('hounddd.mallimportexport.export')) {
                    $buttons['importexport'] = [
                        'label'       => 'hounddd.mallimportexport::lang.menus.importexport',
                        'icon'        => 'icon-retweet',
                        'code'        => 'mall-importexport',
                        'owner'       => 'Offline.Mall',
                        'url'         => Backend::url('offline/mall/products/import'),
                        'permissions' => ['hounddd.mallimportexport.*'],
                    ];
                } else {
                    if ($currentUser->hasPermission('hounddd.mallimportexport.import')) {
                        $buttons['import'] = [
                            'label'       => 'hounddd.mallimportexport::lang.menus.import',
                            'icon'        => 'icon-cloud-upload',
                            'code'        => 'mall-import',
                            'owner'       => 'Offline.Mall',
                            'url'         => Backend::url('offline/mall/products/import'),
                            'permissions' => ['hounddd.mallimportexport.import'],
                        ];
                    }
                    if ($currentUser->hasPermission('hounddd.mallimportexport.export')) {
                        $buttons['export'] = [
                            'label'       => 'hounddd.mallimportexport::lang.menus.export',
                            'icon'        => 'icon-cloud-upload',
                            'code'        => 'mall-export',
                            'owner'       => 'Offline.Mall',
                            'url'         => Backend::url('offline/mall/products/export'),
                            'permissions' => ['hounddd.mallimportexport.export'],
                        ];
                    }
                }

                $manager->addSideMenuItems('Offline.Mall', 'mall-catalogue', $buttons);
            });
        }

        /**
         * Check for user permissions to access import or export routes
         */
        Event::listen(
            'backend.page.beforeDisplay',
            function (
                $backendController,
                $action,
                $params
            ) {
                $currentUser = BackendAuth::getUser();
                if ($currentUser->hasAccess('hounddd.mallimportexport.*')) {
                    if ($action == 'import' && !$currentUser->hasPermission('hounddd.mallimportexport.import')) {
                        return \Backend::redirect('offline/mall/products');
                    }
                    if ($action == 'export' && !$currentUser->hasPermission('hounddd.mallimportexport.export')) {
                        return \Backend::redirect('offline/mall/products');
                    }
                }
            }
        );

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

            // Define property if not already defined
            if (!isset($controller->importExportConfig)) {
                $controller->addDynamicProperty('importExportConfig', $config);
            }
        });
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'hounddd.mallimportexport.import' => [
                'tab' => 'Mall Import/Export',
                'label' => 'hounddd.mallimportexport::lang.permissions.import'
            ],
            'hounddd.mallimportexport.export' => [
                'tab' => 'Mall Import/Export',
                'label' => 'hounddd.mallimportexport::lang.permissions.export'
            ],
        ];
    }
}
