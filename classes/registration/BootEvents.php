<?php namespace Hounddd\MallImportExport\Classes\Registration;

use Backend;
use BackendAuth;
use Illuminate\Support\Facades\Event;
use System\Classes\PluginBase;
use System\Models\PluginVersion;

trait BootEvents
{
    protected function registerEvents()
    {
        $this->registerProductsToolbarEvents();
        $this->registerBackendPagesEvents();
    }

    protected function registerProductsToolbarEvents()
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
    }

    protected function registerBackendPagesEvents()
    {
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
    }
}
