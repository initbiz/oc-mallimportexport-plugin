<?php

namespace Initbiz\MallImportExport\EventHandlers;

use App;
use Event;
use Backend;
use BackendAuth;

class BackendHandler
{
    public function subscribe($event)
    {
        if (App::runningInBackend()) {
            $this->extendBackendNav($event);
            $this->validateAccessToRoutes($event);
        }
    }

    protected function extendBackendNav(Event $event): void
    {
        $event->listen('backend.menu.extendItems', function ($manager) {
            $buttons = [];
            $currentUser = BackendAuth::getUser();

            if (
                $currentUser->hasPermission('initbiz.mallimportexport.import')
                && $currentUser->hasPermission('initbiz.mallimportexport.export')
            ) {
                $buttons['importexport'] = [
                    'label'       => 'initbiz.mallimportexport::lang.menus.importexport',
                    'icon'        => 'icon-retweet',
                    'code'        => 'mall-importexport',
                    'owner'       => 'Offline.Mall',
                    'url'         => Backend::url('offline/mall/products/import'),
                    'permissions' => ['initbiz.mallimportexport.*'],
                ];
            } else {
                if ($currentUser->hasPermission('initbiz.mallimportexport.import')) {
                    $buttons['import'] = [
                        'label'       => 'initbiz.mallimportexport::lang.menus.import',
                        'icon'        => 'icon-cloud-upload',
                        'code'        => 'mall-import',
                        'owner'       => 'Offline.Mall',
                        'url'         => Backend::url('offline/mall/products/import'),
                        'permissions' => ['initbiz.mallimportexport.import'],
                    ];
                }
                if ($currentUser->hasPermission('initbiz.mallimportexport.export')) {
                    $buttons['export'] = [
                        'label'       => 'initbiz.mallimportexport::lang.menus.export',
                        'icon'        => 'icon-cloud-upload',
                        'code'        => 'mall-export',
                        'owner'       => 'Offline.Mall',
                        'url'         => Backend::url('offline/mall/products/export'),
                        'permissions' => ['initbiz.mallimportexport.export'],
                    ];
                }
            }

            $manager->addSideMenuItems('Offline.Mall', 'mall-catalogue', $buttons);
        });
    }

    public function validateAccessToRoutes(Event $event): void
    {
        $event->listen('backend.page.beforeDisplay', function ($backendController, $action, $params) {
            $currentUser = BackendAuth::getUser();
            if ($currentUser->hasAccess('initbiz.mallimportexport.*')) {
                if ($action == 'import' && !$currentUser->hasPermission('initbiz.mallimportexport.import')) {
                    return \Backend::redirect('offline/mall/products');
                }
                if ($action == 'export' && !$currentUser->hasPermission('initbiz.mallimportexport.export')) {
                    return \Backend::redirect('offline/mall/products');
                }
            }
        });
    }
}
