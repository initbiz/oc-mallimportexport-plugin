<?php namespace Hounddd\MallImportExport\Models;

use Backend;
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
        'only_variants',
        'link',
        'admin_link',
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

    /**
     * Product page
     *
     * @var Page
     */
    protected $castAsBolean = [
        'published',
        'allow_out_of_stock_purchases',
    ];

    private $exportLink = false;
    private $exportAdminLink = false;

    public function __construct(array $attributes = [])
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
        if ($this->exportAdminLink) {
            $columns['admin_link'] = 'hounddd.mallimportexport::lang.columns.admin_link';
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

        // Reorder variants just after their parent product
        $productsWithVariants->each(function ($product, $key) use ($products) {
            if (! (bool)$this->only_variants || $product->inventory_management_method === 'single') {
                $products->push($product);
            }
            if ($product->inventory_management_method === 'variant') {
                $product->variants->each(function ($item) use ($products) {
                    $products->push($item);
                });
            }
        });

        // Add other prices
        $products = $products->map(function ($product) {
            return $this->addOtherPrices($product);
        });

        // Add public link to product
        if ($this->link) {
            $this->exportLink = true;
            $columns[] = 'link';
            $products = $products->map(function ($product) {
                return $this->addLink($product);
            });
        }

        // Add admin link to product
        if ($this->admin_link) {
            $this->exportAdminLink = true;
            $columns[] = 'admin_link';
            $products = $products->map(function ($product) {
                return $this->addAdminLink($product);
            });
        }

        $products = $products->each(function ($product) use ($columns) {
            $product->addVisible($columns);
        });

        $collection = collect($products->toArray());
        $collection = $collection->map(function ($item) {
            return $this->emptyToFalse($item);
        });
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
     * Add admin_link property to product's edit page
     *
     * @param Product $product
     * @return Product
     */
    protected function addAdminLink($product)
    {
        $productId = $product->id;
        if ($product instanceof Variant) {
            $productId = $product->product->id;
        }
        $product['admin_link'] = Backend::url('offline/mall/products/update/'. $productId);
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
     * Check for empty values and replace them by a logical false
     *
     * @param mixed $item
     * @return mixed
     */
    protected function emptyToFalse($item)
    {
        if (is_array($item)) {
            foreach ($this->castAsBolean as $key => $attribute) {
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
