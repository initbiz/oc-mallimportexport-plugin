<?php namespace Hounddd\MallImportExport\Models;

use Config;
use Str;

use OFFLINE\Mall\Models\Currency;
use OFFLINE\Mall\Models\Product;
use OFFLINE\Mall\Models\Price;
use OFFLINE\Mall\Models\ProductPrice;
use OFFLINE\Mall\Models\Variant;

class ProductExport extends \Backend\Models\ExportModel
{
    public $requiredPermissions = ['hounddd.mallimportexport.export'];

    public $currencies;

    public $defaultCurrency;

    public function __construct()
    {
        parent::__construct();
        $this->currencies = Currency::orderBy('is_default', 'DESC')->orderBy('sort_order', 'ASC')->get();
        $this->defaultCurrency = Currency::where('is_default', true)->first();
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
            return $this->getOtherPrices($product);
        })
        ->each(function ($product) use ($columns) {
            $product->addVisible($columns);
        });

        $collection = collect($products->toArray());
        $data = $collection->map(function ($item) {
            return $this->encodeArrays($item);
        });

        return $data->toArray();
    }



    private function getOtherPrices($product)
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




    private function encodeArrays($item)
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


    private function getPrices($prices)
    {
        if (count($prices) == 1) {
            $price = $prices[0];
            $prices = $this->formatedPriceNoSymbol($price);
        } else {
            foreach ($prices as $key => $price) {
                if (is_array($price)) {
                    $prices[$key] = [
                        $price['currency']['code'] => $this->formatedPriceNoSymbol($price),
                    ];
                    return $prices;
                }
            }
        }
        return $prices;
    }


    private function formatedPriceNoSymbol($price)
    {
        return trim(str_replace(
            $price['currency']['symbol'],
            '',
            $price['price_formatted']
        ));
        // return number_format($price['price'] / 100, $price['currency']['decimals']);
    }
}
