<?php namespace Hounddd\MallImportExport\Models;

use Config;
use Str;
use Cms\Classes\Page;
use OFFLINE\Mall\Models\Currency;
use OFFLINE\Mall\Models\GeneralSettings;
use OFFLINE\Mall\Models\Product;
use OFFLINE\Mall\Models\Price;
use OFFLINE\Mall\Models\ProductPrice;
use OFFLINE\Mall\Models\Variant;

class ProductExport extends \Backend\Models\ExportModel
{
    public $requiredPermissions = ['hounddd.mallimportexport.export'];

    public $fillable = [
        'link'
    ];

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

    private $exportLink = false;

    public function __construct()
    {
        parent::__construct();
        $this->currencies = Currency::orderBy('is_default', 'DESC')->orderBy('sort_order', 'ASC')->get();
        $this->defaultCurrency = Currency::where('is_default', true)->first();

        $this->productPage    = GeneralSettings::get('product_page', 'product');
        $this->cmsPage = Page::loadCached(config('cms.activeTheme'), $this->productPage);
    }

    /**
     * Used to override column definitions at export time.
     */
    protected function exportExtendColumns($columns)
    {
        if ($this->exportLink) {
            $columns['link'] = 'hounddd.mallimportexport::lang.columns.link';
        }
        return $columns;
    }

    public function exportData($columns, $sessionKey = null)
    {
        $columns[] = 'additional_prices';
        $columns[] = 'customer_group_prices';

        $productsWithVariants = Product::with([
                    'prices',
                    'additional_prices',
                    'customer_group_prices',
                    'variants.customer_group_prices',
                    'variants.additional_prices',
                ])
                ->get();

        $products = collect();

        $productsWithVariants->each(function ($product, $key) use ($products) {
            $products->push($product);
            if ($product->inventory_management_method === 'variant') {
                $product->variants->each(function ($item) use ($products) {
                    $products->push($item);
                });
            }
        });

        $products = $products->map(function ($product) {
            return $this->addOtherPrices($product);
        });

        if ($this->link) {
            $this->exportLink = true;
            $columns[] = 'link';
            $products = $products->map(function ($product) {
                return $this->addLink($product);
            });
        }

        $products = $products->each(function ($product) use ($columns) {
            $product->addVisible($columns);
        });

        $collection = collect($products->toArray());
        $data = $collection->map(function ($item) {
            return $this->encodeArrays($item);
        });

        return $data->toArray();
    }

    /**
     * Add properties for each prices
     *
     * @param Product $product
     * @return Product
     */
    protected function addOtherPrices($product)
    {
        foreach ($product->prices as $key => $price) {
            $product['price__'. $price->currency->code] = $this->formatedPriceNoSymbol($price->toArray());
        }
        foreach ($product->additional_prices as $key => $price) {
            $name = 'additional__'. $price->price_category_id .'__'. $price->currency->code;
            $product[$name] = $this->formatedPriceNoSymbol($price->toArray());
        }
        foreach ($product->customer_group_prices as $key => $price) {
            $name = 'group__'. $price->customer_group_id .'__'. $price->currency->code;
            $product[$name] = $this->formatedPriceNoSymbol($price->toArray());
        }
        return $product;
    }

    /**
     * Add link property to product's page
     *
     * @param Product $product
     * @return Product
     */
    protected function addLink($product)
    {
        if ($this->cmsPage) {
            $pageUrl = $this->cmsPage->url($this->productPage, ['slug' => $product->slug]);
            $product['link'] = $pageUrl;
        }

        return $product;
    }

    /**
     * Return formated price without currency symbol
     *
     * @param array $price
     * @return string
     */
    protected function formatedPriceNoSymbol($price)
    {
        return trim(str_replace(
            $price['currency']['symbol'],
            '',
            $price['price_formatted']
        ));
    }

    /**
     * Encore array values to json
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
        }
        return $item;
    }
}
