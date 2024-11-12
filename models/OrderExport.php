<?php

namespace Initbiz\MallImportExport\Models;

use Backend\Models\ExportModel;
use OFFLINE\Mall\Models\Order;

class OrderExport extends ExportModel
{
    public $requiredPermissions = ['initbiz.mallimportexport.export_orders'];

    public $fillable = [];

    /**
     * Columns
     *
     * @var array
     */
    protected $columns = [];

    public function exportData($columns, $sessionKey = null)
    {
        $records = [];

        $orders = Order::with([
            'products',
            'customer.user',
            'order_state',
            'payment_method',
        ])->get();

        foreach ($orders as $order) {
            $records[] = $this->processOrder($order, $columns);
        }

        return $records;
    }

    public function processOrder(Order $order, array $columns): array
    {
        $data = [];
        foreach ($columns as $column) {
            if ($column === 'shipping_address') {
                $data['shipping_address'] = $this->makeAddressOneLiner($order->shipping_address);
                continue;
            } elseif ($column === 'email') {
                $data['email'] = $order->customer->user->email ?? '';
                continue;
            } elseif ($column === 'firstname') {
                $data['firstname'] = $order->customer->firstname ?? '';
                continue;
            } elseif ($column === 'lastname') {
                $data['lastname'] = $order->customer->lastname ?? '';
                continue;
            } elseif ($column === 'billing_address') {
                $data['billing_address'] = $this->makeAddressOneLiner($order->billing_address);
                continue;
            } elseif ($column === 'currency') {
                $data['currency'] = $order->currency['code'];
                continue;
            } elseif ($column === 'status') {
                $data['status'] = $order->status['code'];
                continue;
            } elseif ($column === 'payment_method') {
                $data['payment_method'] = $order->payment_method->name;
                continue;
            } elseif ($column === 'discounts') {
                $data['discounts'] = '';
                if (!empty($order->discounts)) {
                    $discounts = collect(array_column($order->discounts, 'discount'))->pluck('name')->toArray();
                    $data['discounts'] = $this->makeSpaceSeparated($discounts);
                }
                continue;
            } elseif ($column === 'taxes') {
                $data['taxes'] = $this->makeSpaceSeparatedWithKeys($order->taxes);
                continue;
            } else {
                $data[$column] = $order->$column ?? '';
            }
        }

        return $data;
    }

    public function makeAddressOneLiner($addressData): string
    {
        $parts = array_filter([
            array_get($addressData, 'name'),
            array_get($addressData, 'lines'),
            array_get($addressData, 'zip') . ' ' . array_get($addressData, 'city'),
            array_get($addressData, 'county_or_province'),
            array_get($addressData, 'country')['name'],
        ]);

        return $this->makeSpaceSeparated($parts);
    }

    public function makeSpaceSeparated($data): string
    {
        if (!is_array($data)) {
            if (is_callable($data, 'toArray')) {
                $data = $data->toArray();
            } else {
                return (string) $data;
            }
        }

        $string = '';
        foreach ($data as $value) {
            if (empty($value)) {
                continue;
            }
            $string .= $value . ' ';
        }

        return rtrim($string, ' ');
    }

    public function makeSpaceSeparatedWithKeys($data): string
    {
        if (!is_array($data)) {
            if (is_callable($data, 'toArray')) {
                $data = $data->toArray();
            } else {
                return (string) $data;
            }
        }

        $string = '';
        foreach ($data as $key => $value) {
            if (empty($value)) {
                continue;
            }
            $string .= $key . ": " . $value . ' ';
        }

        return rtrim($string, ' ');
    }

    public function importExportGetFileName()
    {
        return 'orders_export';
    }
}
