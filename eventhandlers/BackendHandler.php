<?php

namespace Initbiz\MallImportExport\EventHandlers;

use App;
use Backend;
use October\Rain\Events\Dispatcher;

class BackendHandler
{
    public function subscribe($event)
    {
        if (App::runningInBackend()) {
            $this->extendBackendNav($event);
        }
    }

    protected function extendBackendNav(Dispatcher $event): void
    {
        $event->listen('backend.menu.extendItems', function ($manager) {
            $items = [
                'import' => [
                    'label'       => 'initbiz.mallimportexport::lang.menus.import',
                    'icon'        => 'icon-cloud-upload',
                    'url'         => Backend::url('initbiz/mallimportexport/importexportproducts/import'),
                    'permissions' => ['initbiz.mallimportexport.import'],
                    'order'       => '800',
                ],
                'export' => [
                    'label'       => 'initbiz.mallimportexport::lang.menus.export',
                    'icon'        => 'icon-cloud-upload',
                    'url'         => Backend::url('initbiz/mallimportexport/importexportproducts/export'),
                    'permissions' => ['initbiz.mallimportexport.export'],
                    'order'       => '801',
                ],
                'export-orders' => [
                    'label'       => 'initbiz.mallimportexport::lang.menus.export_orders',
                    'icon'        => 'icon-cloud-upload',
                    'url'         => Backend::url('initbiz/mallimportexport/exportorders'),
                    'permissions' => ['initbiz.mallimportexport.export_orders'],
                    'order'       => '802',
                ]
            ];

            $manager->addSideMenuItems('Offline.Mall', 'mall-catalogue', $items);
        });
    }
}
