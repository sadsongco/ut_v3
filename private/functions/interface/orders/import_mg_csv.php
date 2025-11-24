<?php

session_start();

require(__DIR__ . "/../../../../functions/functions.php");
require(base_path("classes/Database.php"));
include(base_path("functions/shop/insert_order_into_db.php"));
include_once(base_path("functions/shop/get_cart_contents.php"));
include_once(base_path("functions/shop/get_package_specs.php"));
include_once(base_path("functions/shop/get_shipping_methods.php"));
include_once(base_path("functions/shop/calculate_cart_subtotal.php"));
include_once(base_path("functions/interface/shop/calculate_shipping.php"));
use Database\Database;
$db = new Database('orders');

$fp = fopen(base_path("private/data/orders/mg_orders.csv"), 'r');
$keys = explode(",", trim(fgets($fp)));
// strange custom field hack
if (isset($keys[16])) unset($keys[16]);
if (isset($keys[17])) unset($keys[17]);
foreach($keys as $key=>$keyname) {
    $keyname = strtolower($keyname);
    if ($keyname === "address line 1") $keyname = "delivery-address1";
    if ($keyname === "address line 2") $keyname = "delivery-address2";
    if ($keyname === "city") $keyname = "delivery-town";
    if ($keyname === "post code") $keyname = "delivery-postcode";
    if ($keyname === "country") $keyname = "delivery-country";
    if ($keyname === "item") $keyname = "items";
    $keys[$key] = $keyname;
}
$mg_orders = [];

while(!feof($fp)) {
    $row = explode(",", fgets($fp));
    // strange custom field hack
    if (sizeof($row) === 18) {
        $row[2] = $row[2] . ", " . $row[3];
        $row[3] = $row[4] .", " . $row[5];
        unset($row[4], $row[5]);
        $row = array_values($row);
    }
    if (sizeof($row) !== sizeof($keys)) continue;
    $updated_order = false;
    foreach ($mg_orders as &$mg_order) {
        if ($mg_order['customer order id'] == $row[15]) {
            $mg_order['items'][] = $row[9];
            $updated_order = true;
            break;
        }
    }
    if ($updated_order) continue;
    $item = $row[9];
    $row[9] = [];
    $row[9][] = $item;
    $mg_orders[] = array_combine($keys, $row);
}
foreach ($mg_orders as &$mg_order) {
    $query = "SELECT order_id FROM New_Orders WHERE `mg` = ?";
    $params = [$mg_order['customer order id']];
    $result = $db->query($query, $params)->fetch();
    if ($result && sizeof($result) > 0) {
        echo "ORDER ALREADY IN DATABASE!<br>";
        continue;
    }
    $mg_order['name'] = $mg_order['forename'] . " " . $mg_order['surname'];
    foreach ($mg_order['items'] as &$item) {
        if ($item === '"Rich Inner Life - Signed 12"" LP"') $item = 'Rich Inner Life - signed 12" LP';
        if ($item === '"Rich Inner Life - 12"" LP"') $item = 'Rich Inner Life - 12" LP';
        $query = "SELECT item_id FROM Items WHERE name = ?";
        $params = [$item];
        $item = [
            "item_id" => $db->query($query, $params)->fetch()['item_id'],
            "quantity"=>(int)$mg_order['qty']
        ];
    }
    if ($mg_order['delivery-country'] == "United States") $mg_order['delivery-country'] = "USA";
    $country = $db->query("SELECT country_id, rm_zone FROM Countries WHERE name = ?", [$mg_order['delivery-country']])->fetch();
    if (!$country) throw new Exception("Unknown country: " . $mg_order['delivery-country']);
    $mg_order['rm_zone'] = $country['rm_zone'];
    $mg_order['zone'] = $country['rm_zone'] == "UK" ? "UK" : "ROW";
    $mg_order['delivery-country'] = $country['country_id'];
    
    if ((!isset($mg_order['bundles']) || sizeof($mg_order['bundles']) == 0) && (!isset($mg_order['items']) || sizeof($mg_order['items']) == 0)) {
        header("Location: /shop/");
        exit();
    }
    
    $_SESSION = $mg_order;
    $mg_order['items'] = getCartContents($db);
    $_SESSION = $mg_order;
    $mg_order['totals']['subtotal'] = calculateCartSubtotal($mg_order['items']);
    $_SESSION = $mg_order;    
    $mg_order['package_specs'] = getPackageSpecs($mg_order['items']);
    $_SESSION = $mg_order;
    
    $shipping_options = getShippingMethods($mg_order['rm_zone'], $db);
    if (sizeof($shipping_options) == 0) {
        exit("Sorry, no shipping options available.");
    }
    $default_method = $shipping_options[0];
    $mg_order['shipping_method'] = $default_method;
    $_SESSION = $mg_order;
    
    [$mg_order['totals']['shipping'], $mg_order['package_specs']['package_id'], $mg_order['package_specs']['package_name']] = calculateShipping($db, $mg_order['rm_zone'], $mg_order['shipping_method']);
    $mg_order['totals']['total'] = $mg_order['totals']['subtotal'] + $mg_order['totals']['shipping'];
    $mg_order['totals']['vat'] = $mg_order['totals']['total'] - ($mg_order['totals']['total'] / 1.2);

    $mg_order['shipping_method'] = $mg_order['shipping_method']['shipping_method_id'];
    $mg_order = insertOrderIntoDB($mg_order, $db);
    $query = "UPDATE New_Orders SET transaction_id = ?, mg = ? WHERE order_id = ?";
    $params = [$mg_order['fulfilment order id'], $mg_order['customer order id'], explode("-", $mg_order['order_id'])[1]];
    try {
        $db->query($query, $params);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

header ('HX-Trigger:updateOrderList');
echo "Music Glue Orders updated";
