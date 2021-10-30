<?php namespace Hounddd\MallImportExport\Models;

use Config;
use Lang;
use Str;

use OFFLINE\Mall\Models\Currency;
use OFFLINE\Mall\Models\CustomerGroup;
use OFFLINE\Mall\Models\CustomerGroupPrice;
use OFFLINE\Mall\Models\Product;
use OFFLINE\Mall\Models\Price;
use OFFLINE\Mall\Models\PriceCategory;
use OFFLINE\Mall\Models\ProductPrice;
use OFFLINE\Mall\Models\Variant;

class ProductImport extends \Backend\Models\ImportModel
{
    public $requiredPermissions = ['hounddd.mallimportexport.import'];

    /**
     * @var array The rules to be applied to the data.
     */
    public $rules = [
        'user_defined_id' => 'required',
    ];

    private $additionalPriceCategories;
    private $customerGroups;
    private $currencies;
    private $defaultCurrency;
    private $priceTrad;

    public function __construct()
    {
        parent::__construct();
        $this->currencies = Currency::orderBy('is_default', 'DESC')->orderBy('sort_order', 'ASC')->get();
        $this->defaultCurrency = Currency::where('is_default', true)->first();
        $this->additionalPriceCategories = PriceCategory::orderBy('sort_order', 'ASC')->get();
        $this->customerGroups = CustomerGroup::orderBy('sort_order', 'ASC')->get();
    }


    public function importData($results, $sessionKey = null)
    {
        $this->priceTrad = trans('offline.mall::lang.product.price');

        foreach ($results as $row => $data) {
            try {
                $ref = trim($data['user_defined_id']);

                if ($ref == '') {
                    $this->logSkipped(
                        $row,
                        Lang::get("hounddd.mallimportexport::lang.import.errors.emptyline")
                    );
                } else {
                    $product = Variant::where('user_defined_id', $ref)->first();
                    if (!$product) {
                        $product = Product::where('user_defined_id', $ref)->first();
                    }

                    $data['published'] = $data['published'] == 1 ? true: false;

                    if ($product) {
                        $product->fill($data);
                        $product->save();

                        $this->setPrices(
                            $data,
                            $product,
                            $row,
                            $ref
                        );

                        $this->logUpdated();
                    }
                }
            } catch (\Exception $ex) {
                $this->logError($row, $ex->getMessage());
            }
        }
        return false;
    }




    private function setPrices($data, $product, $row, $ref)
    {
        $skipped = '';

        $this->currencies->each(function (Currency $currency) use (
            $data,
            $product,
            $row,
            $ref,
            &$skipped
        ) {
            // Regular prices
            $price = array_get($data, 'price__'. $currency->code, null);

            if (!is_null($price)) {
                if (!$this->isValidPrice($price)) {
                    $skipped .= Lang::get(
                        "hounddd.mallimportexport::lang.import.errors.notanumber",
                        [
                            'type' => sprintf('%s %s', $this->priceTrad, $currency->symbol),
                            'price' => $price
                        ]
                    );
                } else {
                    $price = $this->floatValue($price);
                    $dataPrice = [
                        'currency_id' => $currency->id,
                    ];

                    if ($product instanceof Product) {
                        $productPrice = ProductPrice::where('currency_id', $currency->id)
                                ->where('product_id', $product->id);
                        $dataPrice['product_id'] = $product->id;
                    } elseif ($product instanceof Variant) {
                        $productPrice = ProductPrice::where('currency_id', $currency->id)
                                ->where('product_id', $product->product->id)
                                ->where('variant_id', $product->id);

                        $dataPrice['product_id'] = $product->product->id;
                        $dataPrice['variant_id'] = $product->id;
                    }

                    if ($currency->is_default) {
                        // If default currency, a price must exist
                        $productPrice = $productPrice->firstOrFail();
                    } else {
                        $productPrice = ProductPrice::firstOrNew($dataPrice);
                    }

                    $productPrice->price = $price;
                    $productPrice->save();
                }
            }

            // Additional prices
            $this->additionalPriceCategories->each(function (PriceCategory $category) use (
                $data,
                $currency,
                $product,
                &$skipped
            ) {
                $price = trim(array_get($data, 'additional__'. $category->id .'__'. $currency->code, null));

                if (!is_null($price)) {
                    if (!$this->isValidPrice($price)) {
                        $skipped .= Lang::get(
                            "hounddd.mallimportexport::lang.import.errors.notanumber",
                            [
                                'type' => sprintf('%s %s', $this->priceTrad, $currency->symbol),
                                'price' => $price
                            ]
                        );
                    } else {
                        if ($product instanceof Product) {
                            $priceableType = 'mall.product';
                            $id = $product->id;
                        } elseif ($product instanceof Variant) {
                            $priceableType = 'mall.variant';
                            $id = $product->product->id;
                        }

                        $productPrice = Price::firstOrNew([
                            'currency_id'       => $currency->id,
                            'priceable_id'      => $id,
                            'priceable_type'    => $priceableType,
                            'price_category_id' => $category->id
                        ]);

                        $productPrice->price = $this->floatValue($price);
                        $productPrice->save();
                    }
                }
            });

            // Custommer's prices
            $this->customerGroups->each(function (CustomerGroup $group) use (
                $data,
                $currency,
                $product,
                &$skipped
            ) {
                $price = trim(array_get($data, 'group__'. $group->id .'__'. $currency->code, null));

                if (!is_null($price)) {
                    if (!$this->isValidPrice($price)) {
                        $skipped .= Lang::get(
                            "hounddd.mallimportexport::lang.import.errors.notanumber",
                            [
                                'type' => sprintf('%s %s', $this->priceTrad, $currency->symbol),
                                'price' => $price
                            ]
                        );
                    } else {
                        if ($product instanceof Product) {
                            $priceableType = 'mall.product';
                            $id = $product->id;
                        } elseif ($product instanceof Variant) {
                            $priceableType = 'mall.variant';
                            $id = $product->product->id;
                        }

                        $productPrice = CustomerGroupPrice::firstOrNew([
                            'currency_id'       => $currency->id,
                            'priceable_id'      => $id,
                            'priceable_type'    => $priceableType,
                            'customer_group_id' => $group->id
                        ]);

                        $productPrice->price = $this->floatValue($price);
                        $productPrice->save();
                    }
                }
            });
        });

        if ($skipped != '') {
            $skipped = Lang::get(
                "hounddd.mallimportexport::lang.import.errors.forref",
                [
                    'ref' => $ref
                ]
            ). $skipped .'.';

            $this->logSkipped($row, $skipped);
        }
    }



    private function isValidPrice($price)
    {
        $price = trim($price);
        if (is_numeric($price)) {
            $valid = true;
        } elseif (is_float($price)) {
            $valid = true;
        } elseif (is_float($this->floatValue($price))) {
            $valid = true;
        } elseif ($price == '') {
            $valid = true;
        } else {
            $valid = false;
        }

        return $valid;
    }


    private function floatValue($val)
    {
        $val = str_replace(",", ".", $val);
        $val = preg_replace('/\.(?=.*\.)/', '', $val);
        return is_numeric($val) ? floatval($val) : $val;
    }
}
