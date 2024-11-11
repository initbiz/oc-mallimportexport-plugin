<?php

namespace Initbiz\MallImportExport\Controllers;

use Backend;
use Redirect;
use BackendMenu;
use Backend\Classes\Controller;

class ImportExportProducts extends Controller
{
    public $implement = [
        \Backend\Behaviors\ImportExportController::class
    ];

    public $importExportConfig = 'config_import_export.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('OFFLINE.Mall', 'mall-catalogue', 'import');
    }

    public function export()
    {
        BackendMenu::setContext('OFFLINE.Mall', 'mall-catalogue', 'export-products');
        return $this->asExtension('ImportExportController')->export();
    }

    public function index()
    {
        $url = Backend::url("initbiz/mallimportexport/importexportproducts/import");
        return Redirect::to($url);
    }
}
