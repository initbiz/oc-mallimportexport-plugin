<?php

namespace Initbiz\MallImportExport\Controllers;

use Backend;
use Redirect;
use BackendMenu;
use Backend\Classes\Controller;

class ExportOrders extends Controller
{
    public $implement = [
        \Backend\Behaviors\ImportExportController::class
    ];

    public $importExportConfig = 'config_export_orders.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('OFFLINE.Mall', 'mall-catalogue', 'export-orders');
    }

    public function index()
    {
        $url = Backend::url("initbiz/mallimportexport/exportorders/export");
        return Redirect::to($url);
    }
}
