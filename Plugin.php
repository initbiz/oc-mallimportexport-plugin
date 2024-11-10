<?php

namespace Initbiz\MallImportExport;

use Event;
use System\Classes\PluginBase;

/**
 * MallImportExport Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = [
        'Offline.Mall'
    ];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'initbiz.mallimportexport::lang.plugin.name',
            'description' => 'initbiz.mallimportexport::lang.plugin.description',
            'author'      => 'Initbiz',
            'icon'        => 'icon-retweet',
        ];
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        Event::subscribe(\Initbiz\MallImportExport\EventHandlers\BackendHandler::class);
        Event::subscribe(\Initbiz\MallImportExport\EventHandlers\OfflineMallHandler::class);
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'initbiz.mallimportexport.import' => [
                'tab' => 'Mall Import/Export',
                'label' => 'initbiz.mallimportexport::lang.permissions.import'
            ],
            'initbiz.mallimportexport.export' => [
                'tab' => 'Mall Import/Export',
                'label' => 'initbiz.mallimportexport::lang.permissions.export'
            ],
        ];
    }
}
