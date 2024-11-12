<?php

namespace Initbiz\MallImportExport\Models;

use Config;
use Backend;
use Cms\Classes\Page;
use Cms\Classes\Theme;
use Backend\Models\ExportModel;
use OFFLINE\Mall\Models\Product;
use OFFLINE\Mall\Models\Variant;
use OFFLINE\Mall\Models\Currency;
use OFFLINE\Mall\Models\GeneralSettings;
use October\Rain\Database\Collection;
use October\Rain\Exception\ApplicationException;

class ProductExport extends ExportModel
{
    public $requiredPermissions = ['initbiz.mallimportexport.export'];

    public $fillable = [
        'only_variants',
        'link',
        'admin_link',
    ];

    /**
     * Columns
     *
     * @var array
     */
    protected $columns = [];

    /**
     * List of available currencies
     *
     * @var collection
     */
    protected $currencies;

    /**
     * Default currency
     *
     * @var Currency
     */
    protected $defaultCurrency;

    /**
     * Product page identifier
     *
     * @var string
     */
    protected $productPage;

    /**
     * Product page
     *
     * @var Page
     */
    protected $cmsPage;

    /**
     * Product page
     *
     * @var Page
     */
    protected $castAsBoolean = [
        'published',
        'allow_out_of_stock_purchases',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct();
        $this->currencies = Currency::orderBy('is_default', 'DESC')->orderBy('sort_order', 'ASC')->get();
        $this->defaultCurrency = Currency::where('is_default', true)->first();

        $this->productPage = GeneralSettings::get('product_page', 'product');
        $theme = Theme::getActiveTheme();
        $this->cmsPage = Page::loadCached($theme, $this->productPage);
    }

    /**
     * Used to override column definitions at export time.
     */
    protected function exportExtendColumns($columns)
    {
        $this->columns = array_merge($columns, $this->columns);

        if ($this->link) {
            $this->columns['link'] = 'initbiz.mallimportexport::lang.columns.link';
        }

        if ($this->admin_link) {
            $this->columns['admin_link'] = 'initbiz.mallimportexport::lang.columns.admin_link';
        }

        return $this->columns;
    }

    public function exportData($columns, $sessionKey = null)
    {
        $products = Product::with([
            'prices',
            'additional_prices',
            'customer_group_prices',
            'variants.customer_group_prices',
            'variants.additional_prices',
        ])->get();

        return $this->processProducts($products);
    }

    public function processExportData($columns, $results, $options)
    {
        // Validate
        if (!$results) {
            throw new ApplicationException(__("There was no data supplied to export"));
        }

        // Extend columns
        $columns = $this->exportExtendColumns($columns);

        // Save for download
        $fileName = uniqid('oc');

        $queueEnabled = Config::get('initbiz.mallimportexport::export_queue.enabled');

        // Prepare export
        if ($this->file_format === 'json') {
            $fileName .= 'xjson';
            if (!$queueEnabled) {
                $options['savePath'] = $this->getTemporaryExportPath($fileName);
            }
            $output = $this->processExportDataAsJson($columns, $results, $options);
        }
        else {
            $fileName .= 'xcsv';
            if (!$queueEnabled) {
                $options['savePath'] = $this->getTemporaryExportPath($fileName);
            }
            $output = $this->processExportDataAsCsv($columns, $results, $options);
        }

        if ($queueEnabled) {
            return $output;
        }

        return $fileName;
    }

    public function processProducts(Collection $products): array
    {
        $processedProducts = [];

        foreach ($products as $product) {
            if (! (bool)$this->only_variants || $product->inventory_management_method === 'single') {
                $processedProducts[] = $this->processProduct($product);
            }
            if ($product->inventory_management_method === 'variant') {
                foreach ($product->variants as $variant) {
                    $processedProducts[] = $this->processProduct($variant);
                }
            }
        }

        return $processedProducts;
    }

    protected function processProduct($product): array
    {
        $product = $this->emptyToFalse($product);
        $product = $this->encodeArrays($product);

        $productArray = $product->toArray();

        foreach ($product->prices as $key => $price) {
            $columnName = 'price__' . $price->currency->code;
            if (!isset($this->columns[$columnName])) {
                $this->columns[$columnName] = $columnName;
            }
            $productArray[$columnName] = $price->integer;
        }

        foreach ($product->additional_prices as $key => $price) {
            $columnName = 'additional__' . $price->price_category_id . '__' . $price->currency->code;
            if (!isset($this->columns[$columnName])) {
                $this->columns[$columnName] = $columnName;
            }
            $productArray[$columnName] = $price->integer;
        }
        foreach ($product->customer_group_prices as $key => $price) {
            $columnName = 'group__' . $price->customer_group_id . '__' . $price->currency->code;
            if (!isset($this->columns[$columnName])) {
                $this->columns[$columnName] = $columnName;
            }
            $productArray[$columnName] = $price->integer;
        }

        // Add public link to product
        if ($this->link && $this->cmsPage) {
            $pageUrl = \Cms::pageUrl($this->cmsPage->getBaseFileName(), ['slug' => $product->slug]);
            $productArray['link'] = $pageUrl;
        }

        // Add admin link to product
        if ($this->admin_link) {
            $productId = $product->id;
            if ($product instanceof Variant) {
                $productId = $product->product->id;
            }
            $productArray['admin_link'] = Backend::url('offline/mall/products/update/' . $productId);
        }

        return $productArray;
    }

    /**
     * Add properties for each prices
     *
     * @param Product $product
     * @return Product
     */
    protected function addOtherPrices($product)
    {
        return $product;
    }

    /**
     * Return formated price without currency symbol
     *
     * @param array $price
     * @return string
     */
    protected function formatPriceNoSymbol($price)
    {
        return trim(str_replace(
            $price['currency']['symbol'],
            '',
            $price['price_formatted']
        ));
    }

    /**
     * Check for empty values and replace them by a logical false
     *
     * @param mixed $item
     * @return mixed
     */
    protected function emptyToFalse($item)
    {
        if (is_array($item)) {
            foreach ($this->castAsBoolean as $key => $attribute) {
                $item[$attribute] = array_get($item, $attribute, 0) == 1 ?: 0;
            }
        }
        return $item;
    }

    /**
     * Encode array values to json
     *
     * @param mixed $item
     * @return mixed
     */
    protected function encodeArrays($item)
    {
        if (is_array($item)) {
            foreach ($item as $key => $value) {
                if (is_array($value)) {
                    $item[$key] = json_encode($value);
                }
            }
            $item['published'] = $item['published'] ?: 0;
        }
        return $item;
    }
}
