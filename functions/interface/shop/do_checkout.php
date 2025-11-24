<?php

session_start();

include("../../functions.php");
require(base_path("classes/Database.php"));
require(base_path("classes/SUCheckout.php"));
require(base_path("functions/shop/get_cart_contents.php"));
require(base_path("functions/shop/calculate_cart_subtotal.php"));
require(base_path("functions/interface/shop/calculate_shipping.php"));
require(base_path("functions/shop/insert_order_into_db.php"));

use Database\Database;
$db = new Database('orders');

// load mustache template engine
require base_path('../lib/mustache.php-main/src/Mustache/Autoloader.php');
Mustache_Autoloader::register();
$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('views')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('views/partials'))
));


// reduce stock by item quantity
try {
    if (isset($_SESSION['items'])) {
        foreach ($_SESSION['items'] AS $item) {
            reduceStock($item, $db);
        }
    }
    if (isset($_SESSION['bundles'])) {
        foreach ($_SESSION['bundles'] AS $bundle) {
            foreach ($bundle['items'] AS $item) {
                $item['quantity'] = $bundle['quantity'];
                reduceStock($item, $db);
            }
        }
    }
}
catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
$order_details = $_POST;
$country_code = $db->query("SELECT country_code FROM Countries WHERE country_id = ?", [$_POST['billing-country']])->fetch();
$order_details['billing-country-code'] = $country_code['country_code'];

$order_details['items'] = getCartContents($db);
$order_details['totals']['subtotal'] = calculateCartSubtotal($order_details['items']);
$order_details['totals']['shipping'] = 0;
$order_details['shipping_method'] = 1;

if ($_SESSION['shipping_method']['shipping_method_id'] != 1) {
    [$order_details['totals']['shipping'], $package_id, $package_name] = calculateShipping($db, $_SESSION['rm_zone'], $_SESSION['shipping_method']);
    $order_details['shipping_method'] = $_SESSION['shipping_method']['shipping_method_id'];
}

$order_details['totals']['total'] = $order_details['totals']['subtotal'] + $order_details['totals']['shipping'];
// VAT payable on orders for UK or Isle Of Man
$order_details['totals']['vat'] = $order_details['delivery-country'] == '31' || $order_details['delivery-country'] == '215' ? calculateVAT($order_details) : NULL;
$order_details['package_specs'] = $_SESSION['package_specs'];

$saved_order = insertOrderIntoDB($order_details, $db);

$_SESSION['order_id'] = $saved_order['order_id'];

use SUCheckout\SUCheckout;
$checkout = new SUCheckout($saved_order);

$response = $checkout->createCheckout()->getResponse();
if (isset($order_details['items']['bundles']['items'])) $items = array_merge($order_details['items']['items'], $order_details['items']['bundles']['items']);
else $items = $order_details['items']['items'];

echo $m->render('shop/payment', ["checkout_id"=>$response->id, "order_id"=>$saved_order['order_id'], "name"=>$order_details['name'], "items"=>$items, "subtotal"=>$order_details['totals']['subtotal'], "shipping"=>$order_details['totals']['shipping'], "vat"=>$order_details['totals']['vat'], "amount"=>$order_details['totals']['total']]);

function reduceStock($item, $db) {
    if ($item['option_id']) {
        $query = "SELECT option_stock FROM Item_options WHERE item_option_id = ?";
        $stock = $db->query($query, [$item['option_id']])->fetch();
        if ($stock['option_stock'] < $item['quantity']) {
            /** deal with out of stock  */
            echo "Out of stock";
            exit();
        }
        $query = "UPDATE Item_options SET option_stock = option_stock - ? WHERE item_option_id = ?";
        $params = [$item['quantity'], $item['option_id']];
        $db->query($query, $params);
    } else {
        $query = "SELECT stock FROM Items WHERE item_id = ?";
        $stock = $db->query($query, [$item['item_id']])->fetch();
        if ($stock['stock'] < $item['quantity']) {
            /** deal with out of stock  */
            echo "Out of stock";
            exit();
        }
        $query = "UPDATE Items SET stock = stock - ? WHERE item_id = ?";
        $params = [$item['quantity'], $item['item_id']];
        $db->query($query, $params);
    }

}

function calculateVAT($order_details) {
    return $order_details['totals']['total'] - ($order_details['totals']['total'] / ((100 + VAT_RATE_PC) / 100));
}