<?php

namespace Initbiz\MallImportExport\Controllers;

use Cache;
use Queue;
use Config;
use Backend;
use Redirect;
use BackendMenu;
use Backend\Classes\Controller;
use OFFLINE\Mall\Models\Product;
use Initbiz\MallImportExport\Jobs\ExportProductsJob;

class ImportExportProducts extends Controller
{
    public const EXPORT_FILE_CACHE_KEY = 'initbiz.mallimportexport.export-ongoing';

    public const EXPORT_FILENAMES = [
        "products.csv",
        "products.json",
    ];

    public $implement = [
        \Backend\Behaviors\ImportExportController::class
    ];

    public $importExportConfig = 'config_import_export.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('OFFLINE.Mall', 'mall-catalogue', 'import');

        $queueEnabled = Config::get('initbiz.mallimportexport::export_queue.enabled');
        $this->vars['exportQueueEnabled'] = $queueEnabled;

        if (!$queueEnabled) {
            return;
        }

        $file = '';
        foreach (self::EXPORT_FILENAMES as $filename) {
            $filepath = temp_path($filename);
            if (file_exists($filepath)) {
                $file = $filepath;
            }
        }

        $this->vars['file'] = $file;
        $this->vars['exportOngoing'] = Cache::get(self::EXPORT_FILE_CACHE_KEY);
    }

    public function export()
    {
        BackendMenu::setContext('OFFLINE.Mall', 'mall-catalogue', 'export-products');
        return $this->asExtension('ImportExportController')->export();
    }

    public function index()
    {
        $url = Backend::url("initbiz/mallimportexport/importexportproducts/export");
        return Redirect::to($url);
    }

    public function importExportGetFileName()
    {
        return "products";
    }

    public function onExport(?array $data = [])
    {
        if (empty($data)) {
            $data = post();
        }

        $queueEnabled = Config::get('initbiz.mallimportexport::export_queue.enabled');

        if (!$queueEnabled) {
            return $this->asExtension('ImportExportController')->onExport();
        }

        foreach (self::EXPORT_FILENAMES as $filename) {
            $filepath = temp_path($filename);
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
        Cache::put(self::EXPORT_FILE_CACHE_KEY, '0', 3600);

        $columns = [];
        $definedColumns = $data['export_columns'] ?? [];
        foreach ($definedColumns as $column) {
            $columns[$column] = $column;
        }

        $optionData = $data['ExportOptions'] ?? null;

        $exportOptions = $this->getFormatOptionsForModel();
        $exportOptions['sessionKey'] = $data['_session_key'] ?? null;

        Product::select('id')->chunk(1, function ($products) use ($columns, $exportOptions, $optionData) {
            Cache::increment(self::EXPORT_FILE_CACHE_KEY);
            Queue::push(ExportProductsJob::class, [
                'columns' => $columns,
                'exportOptions' => $exportOptions,
                'optionData' => $optionData,
                'ids' => $products->pluck('id')->toArray(),
            ]);

            return false;
        });

        return $this->makePartial('export_form_queue');
    }

    protected function getFormatOptionsForModel(): array
    {
        $options = [
            'fileFormat' => post('file_format', $this->getConfig('defaultFormatOptions[fileFormat]')),
            'delimiter' => post('format_delimiter', $this->getConfig('defaultFormatOptions[delimiter]')),
            'enclosure' => post('format_enclosure', $this->getConfig('defaultFormatOptions[enclosure]')),
            'escape' => post('format_escape', $this->getConfig('defaultFormatOptions[escape]')),
            'encoding' => post('format_encoding', $this->getConfig('defaultFormatOptions[encoding]')),
            'firstRowTitles' => (bool) post('first_row_titles', $this->getConfig('defaultFormatOptions[firstRowTitles]', true)),
            'customJson' => $this->getConfig('defaultFormatOptions[customJson]'),
        ];

        if ($options['fileFormat'] !== 'csv_custom') {
            $options['delimiter'] = null;
            $options['enclosure'] = null;
            $options['escape'] = null;
            $options['encoding'] = null;
        }

        return $options;
    }

    public function getConfig($name = null, $default = null)
    {
        return $this->asExtension('ImportExportController')->getConfig($name, $default);
    }
}
