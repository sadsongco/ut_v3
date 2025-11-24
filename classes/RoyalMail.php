<?php

namespace RoyalMail;

use DateTime;
use PDOException;

include_once(base_path("functions/shop/get_cart_contents.php"));
include_once(base_path("functions/shop/get_package_specs.php"));
include_once(base_path("functions/shop/get_shipping_methods.php"));
include_once(base_path("functions/shop/calculate_cart_subtotal.php"));
include_once(base_path("functions/interface/shop/calculate_shipping.php"));

// for old orders
define('SHIPPING_METHODS_MAP', [
    "First Class (1 - 2 days)" => 3,
    "Second Class" => 4,
    "Europe" => 6,
    "Rest Of World" => 6,
]);
define('PACKAGE_FORMATS', [
    "LARGE_LETTER" => [
        "name" => "large letter",
        "weight_min" => 0,
        "weight_max" => 249,
    ],
    "SMALL_PARCEL" => [
        "name" => "small parcel",
        "weight_min" => 250,
        "weight_max" => 1999,
    ],
    "MEDIUM_PARCEL" => [
        "name" => "medium parcel",
        "weight_min" => 2000,
        "weight_max" => 9999,
    ]
]);

function jsFormatDate($date) {
    $dateObj = new DateTime($date);
    return date_format($dateObj, 'Y-m-d\TH:i:s\Z');
}


class RoyalMail {

    protected $db;
    protected $order_id;
    protected $order_data;
    protected $rm_order;
    protected $order_outcomes;
    protected $orders_table;
    protected $order_items_table;
    protected $old_order;

    function __construct($order_id, $db, $old=false)
    {
        $this->orders_table = $old ? "Orders" : "New_Orders";
        $this->order_items_table = $old ? "Order_items" : "New_Order_items";
        $this->old_order = $old;
        $this->db = $db;
        $this->order_id = $order_id;
        $this->getOrderData();
    }

    private function getOrderData()
    {
        $package_specs = "";
        if (!$this->old_order) {
            $package_specs = $this->orders_table . ".package_specs, ";
        }
        try {
            $query = "SELECT
                " . $this->orders_table . ".order_id,
                " . $this->orders_table . ".sumup_id,
                TRIM(" . $this->orders_table . ".shipping_method) AS shipping_method,
                " . $this->orders_table . ".shipping,
                " . $this->orders_table . ".subtotal,
                " . $this->orders_table . ".vat,
                " . $this->orders_table . ".total,
                " . $this->orders_table . ".order_date,
                $package_specs
                Customers.name,
                Customers.address_1,
                Customers.address_2,
                Customers.city,
                Customers.postcode,
                Customers.country,
                Customers.email
            FROM " . $this->orders_table . "
            LEFT JOIN Customers ON " . $this->orders_table . ".customer_id = Customers.customer_id
            WHERE `order_id` = ?
            ";
            $params = [$this->order_id];
            $this->order_data = $this->db->query($query, $params)->fetch();
            if (!$this->old_order) $this->order_data['package_specs'] = json_decode($this->order_data['package_specs'], true);
            $this->getCountryCode();
            $this->getShippingMethod();
            $this->getItems();
            if ($this->old_order) $this->order_data['weight'] = getPackageWeight($this->order_data);
        } catch (PDOException $e) {
            echo $e->getMessage(); 
        }
    }

    private function getCountryCode ()
    {
        $query = "SELECT country_code FROM Countries WHERE country_id = ?";
        if ($this->old_order) $query = "SELECT country_code FROM Countries WHERE name = ?";
        $params = [$this->order_data['country']];
        $result = $this->db->query($query, $params)->fetch();
        $this->order_data['country_code'] = $result['country_code'];
    }

    private function getShippingMethod()
    {
        if (!is_numeric($this->order_data['shipping_method'])) {
            $this->order_data['shipping_method'] = SHIPPING_METHODS_MAP[$this->order_data['shipping_method']];
        }
        $query = "SELECT service_name, service_code FROM Shipping_methods WHERE shipping_method_id = ?";
        $params = [$this->order_data['shipping_method']];
        $this->order_data['rm_shipping_method'] = $this->db->query($query, $params)->fetch();
    }

    private function getItems()
    {
        $query = "SELECT
            Items.name,
            Items.price,
            Items.weight,
            Items.customs_description,
            Items.customs_code,
            Items.sku,
            Item_options.option_name,
            New_Order_items.option_id,
            New_Order_items.quantity
            FROM New_Order_items
            JOIN Items ON New_Order_items.item_id = Items.item_id
            LEFT JOIN Item_options ON New_Order_items.option_id = Item_options.item_option_id
            WHERE New_Order_items.order_id = ?
            AND order_bundle_id IS NULL
            ";
        $params = [$this->order_id];
        $items = $this->db->query($query, $params)->fetchAll();
        foreach ($items as &$item) {
            $item['weight'] *= 1000; // item weights in annoying kg
            if ($item['option_id']) {
                $query = "SELECT option_weight FROM Item_options WHERE item_option_id = ?";
                $params = [$item['option_id']];
                $item['weight'] = $this->db->query($query, $params)->fetch()['option_weight'];
            }
        }
        $query = "SELECT
            Items.name,
            Items.price,
            Items.weight,
            Items.customs_description,
            Items.customs_code,
            Items.sku,
            Item_options.option_name,
            New_Order_items.option_id,
            New_Order_items.quantity
            FROM New_Order_items
            JOIN Items ON New_Order_items.item_id = Items.item_id
            LEFT JOIN Item_options ON New_Order_items.option_id = Item_options.item_option_id
            WHERE New_Order_items.order_id = ?
            AND order_bundle_id IS NOT NULL
            ";
        $params = [$this->order_id];
        $bundle_items = $this->db->query($query, $params)->fetchAll();
        foreach ($bundle_items as &$item) {
            $item['weight'] *= 1000; // item weights in annoying kg
            if ($item['option_id']) {
                $query = "SELECT option_weight FROM Item_options WHERE item_option_id = ?";
                $params = [$item['option_id']];
                $item['weight'] = $this->db->query($query, $params)->fetch()['option_weight'];
            }
        }
        $this->order_data['items'] = array_merge($items, $bundle_items);
    }

    public function displayOrderData()
    {
        return $this->order_data;
    }

    public function displayRMOrder()
    {
        return $this->rm_order;
    }

    public function createRMOrder()
    {
        $this->order_data['order_date'] = jsFormatDate($this->order_data['order_date']);
        if (!isset($this->order_data['rm_shipping_method']['service_code'])) {
            $this->getShippingMethod();
        }
        if (!$this->order_data['rm_shipping_method']['service_code']) return false;
        $order_items = [];
        foreach($this->order_data['items'] as $item) {
            $order_items[] = $this->createRMItem($item);
        }
        $this->order_data['items'] = $order_items;
        if ($this->old_order) {
            $weight = $this->order_data['weight'];
            foreach (PACKAGE_FORMATS as $package_format) {
                if ($weight > $package_format['weight_min'] && $weight <= $package_format['weight_max']) {
                    $package_format = $package_format['name'];
                    break;
                }
            }
        } else {
            if (isset($this->order_data['package_specs']['e_delivery'])) return false;
            if (!isset($this->order_data['package_specs']['package_name'])) {
                return false;
            }
            $weight = $this->order_data['package_specs']['weight'];
            $package_format = strtolower($this->order_data['package_specs']['package_name']);
        }
        $emailNotification = $this->order_data['country_code'] == "GB" ? true : false;
        $this->rm_order = [
            "orderReference"=>$this->order_data['order_id'],
            "recipient"=>[
                "address"=>[
                "fullName"=>$this->order_data['name'],
                "companyName"=>"",
                "addressLine1"=>$this->order_data['address_1'],
                "addressLine2"=>$this->order_data['address_2'] ?? "",
                "addressLine3"=>"",
                "city"=>$this->order_data['city'],
                "county"=>"",
                "postcode"=>$this->order_data['postcode'],
                "countryCode"=>$this->order_data['country_code']
                ],
                "phoneNumber"=>"",
                "emailAddress"=>$this->order_data['email']
            ],
            "sender"=>[
                "tradingName"=>"Unbelievable Truth",
                "phoneNumber"=>"07787 782550",
                "emailAddress"=>"info@unbelievabletruth.co.uk",
                "addressBookReference"=>"001"
            ],
            "packages"=>[
                [
                    "weightInGrams"=>(int)$weight,
                    "packageFormatIdentifier"=>$package_format,
                    "contents"=>$order_items
                ],
            ],
            "orderDate"=>$this->order_data['order_date'],
            "plannedDespatchDate"=>"",
            "subtotal"=>(float)$this->order_data['subtotal'],
            "shippingCostCharged"=>(float)$this->order_data['shipping'],
            "otherCosts"=>"0",
            "total"=>(float)$this->order_data['total'],
            "currencyCode"=>"GBP",
            "postageDetails"=>[
                "sendNotificationsTo"=>"recipient",
                "serviceCode"=>$this->order_data['rm_shipping_method']['service_code'],
                "serviceRegisterCode"=>"",
                "receiveEmailNotification"=>$emailNotification,
                "receiveSmsNotification"=>false,
                "guaranteedSaturdayDelivery"=>false,
                "requestSignatureUponDelivery"=>false,
                "isLocalCollect"=>false
            ],
            "tags"=>[
                [
                "key"=>"string",
                "value"=>"string"
                ]
            ],
            "label"=>[
                "includeLabelInResponse"=>false,
                "includeCN"=>false,
                "includeReturnsLabel"=>false
            ],
        ];
    }

    private function createRMItem($item) {
        if (!isset($item['sku'])) $item['sku'] = "";
        if ($item['option_name'] !== "") $item['name'] = $item['name'] . " - " . $item['option_name'];
        $rm_item = [
            "name"=>$item['name'],
            "SKU"=>$item['sku'],
            "quantity"=>$item['quantity'],
            "unitValue"=>$item['price'],
            "unitWeightInGrams"=>(int)$item['weight'],
            "customsDescription"=>$item['customs_description'],
            "extendedCustomsDescription"=>$item['name'],
            "customsCode"=>$item['customs_code'],
            "originCountryCode"=>"GBR",
            "customsDeclarationCategory"=>"SaleOfGoods",
            "requiresExportLicence"=>false,
            "stockLocation"=>"GB"
        ];
        return $rm_item;
    }

    public function submitRMOrder()
    {
        $data = [
            "items"=>[
                $this->rm_order
            ]
        ];

        if (sizeof($data['items']) == 0) {
            echo "No orders to submit.<br>";
            exit();
        }

        $path = RM_BASE_URL."/orders";
        // $path = RM_BASE_URL."/version";
        $headers = [
            "Authorization: " . RM_API_KEY,
            "Content-Type: application/json"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $path);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $rm_order);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $responseObj = json_decode($response);

        $order_outcomes = [];

        if (isset($responseObj->createdOrders)) {
            foreach($responseObj->createdOrders as $successful_order) {
                $created_on = str_replace(["T", "Z"], [" ", ""], $successful_order->createdOn);
                $query = "UPDATE `" . $this->orders_table . "`
                SET 
                `rm_order_identifier` = ?,
                `rm_created` = ?
                WHERE `order_id` = ?";
                $params = [
                    (int)$successful_order->orderIdentifier,
                    $created_on,
                    (int)$successful_order->orderReference,
                ];
                $stmt = $this->db->query($query, $params);
                if ($this->db->rowCount($stmt) == 0) {
                    array_push($order_outcomes, ['status'=>"FAILED to update database for " . $successful_order->orderReference . " : " . $this->db->error, 'data'=>$successful_order]);
                    continue;
                }
                array_push($order_outcomes, ['status'=>"Order id " . $successful_order->orderReference . " submitted to Royal Mail", 'data'=>$successful_order]);
            }
        }

        if (isset($responseObj->failedOrders)) {
            foreach($responseObj->failedOrders as $failed_order) {
                array_push($order_outcomes, ['status'=>"FAILED TO CREATE ORDER: " . $failed_order->errors[0]->errorMessage, 'data'=>$failed_order]);
            };
        }
        $this->order_outcomes = $order_outcomes;
        return $this;
    }

    public function getOrderOutcomes() {
        return $this->order_outcomes;
    }

};

function getPackageWeight($order) {
    $weight = 0;
    foreach ($order['items'] as $item) {
        $weight += $item['weight'] * $item['quantity'];
    }
    return ($weight * 1000) + 160;
}
