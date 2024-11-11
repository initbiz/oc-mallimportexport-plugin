<?php

namespace Initbiz\MallImportExport\Jobs;

use Cache;
use OFFLINE\Mall\Models\Product;
use Illuminate\Contracts\Queue\Job;
use Initbiz\MallImportExport\Models\ProductExport;
use Initbiz\MallImportExport\Controllers\ImportExportProducts;

class ExportProductsJob
{
    public function fire(Job $job, $data)
    {
        try {
            $this->handle($data);
        } catch (\Throwable $th) {
            Cache::decrement(ImportExportProducts::EXPORT_FILE_CACHE_KEY);
            throw $th;
        }

        Cache::decrement(ImportExportProducts::EXPORT_FILE_CACHE_KEY);

        $job->delete();
    }

    public function handle(array $data)
    {
        $columns = $data['columns'];
        $exportOptions = $data['exportOptions'];
        $optionData = $data['optionData'];
        $ids = $data['ids'];

        $model = new ProductExport($optionData);
        $model->file_format = $exportOptions['fileFormat'] ?? 'json';

        $products = Product::with([
            'prices',
            'additional_prices',
            'customer_group_prices',
            'variants.customer_group_prices',
            'variants.additional_prices',
        ])->whereIn('id', $ids)->get();

        $processedArray = $model->processProducts($products);
        $content = $model->processExportData($columns, $processedArray, $optionData);

        $extension = $exportOptions['fileFormat'];
        if ($extension === 'csv_custom') {
            $extension = 'csv';
        }

        $filePath = temp_path('products.' . $extension);

        // Keep the contents
        if (file_exists($filePath)) {
            if ($extension === 'json') {
                $oldContentParsed = json_decode(file_get_contents($filePath), true);
                if (is_array($oldContentParsed)) {
                    $currentContentParsed = json_decode($content, true);
                    $content = json_encode(array_merge($oldContentParsed, $currentContentParsed));
                }
            } elseif ($extension === 'csv') {
                $offset = strpos($content, "\n");
                $content = substr($content, $offset);
            }
        }

        $content = rtrim($content);
        file_put_contents($filePath, $content, FILE_APPEND | LOCK_EX);
    }
}
