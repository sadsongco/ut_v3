<?php


include("../../functions.php");
require(base_path("classes/Database.php"));
require(base_path("classes/SUCheckout.php"));
require(base_path("functions/shop/get_cart_contents.php"));
require(base_path("functions/shop/calculate_cart_subtotal.php"));
require(base_path("functions/interface/shop/calculate_shipping.php"));
require(base_path("functions/shop/insert_order_into_db.php"));

if (session_status() == PHP_SESSION_NONE) session_start();

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

function artprintInCart($db) {
    // is an artprint in items?
    if (!isset($_SESSION['items'])) return false;
    if (isset($_SESSION['items'])) {
        foreach ($_SESSION['items'] AS $item) {
            if ($item['item_id'] == ARTPRINT_ID) {
                return true;
            }
        }
    }
    return false;
}

function getOrderToAddTo($db) {
    $query = "SELECT customer_id FROM Customers WHERE email = ?";
    $customer = $db->query($query, [$_POST['email']])->fetch();
    if (empty($customer)) return false;
    $query = "SELECT
            New_Orders.order_id,
            CONCAT(DATE_FORMAT(New_Orders.order_date, '%y%m%d'), '-', New_Orders.order_id) AS order_no
        FROM
            New_Orders
        JOIN
            New_Order_items
        ON
            New_Order_items.order_id = New_Orders.order_id
        WHERE
            customer_id = ?
        AND
            transaction_id IS NOT NULL
        AND
            New_Order_items.item_id = ?
        ORDER BY
            order_date DESC
        LIMIT
            1;";
    $order_id = $db->query($query, [$customer['customer_id'], DOUBLE_LP_ID])->fetch();
    return empty($order_id) ? false : $order_id;
}

function validArtprintOrder($db) {
    $customer = $db->query("SELECT customer_id FROM Customers WHERE email = ?", [$_POST['email']])->fetch();
    $open_orders = $db->query("SELECT
        order_id
    FROM New_Orders WHERE customer_id = ? AND dispatched IS NULL", [$customer['customer_id']])->fetchAll();
    if (sizeof($open_orders) == 0) return false;
    $valid = false;
    foreach ($open_orders AS $open_order) {
        $valid_order = $db->query("SELECT COUNT(*) AS count FROM New_Order_items WHERE order_id = ? AND item_id = ?", [$open_order['order_id'], DOUBLE_LP_ID])->fetch();
        if ($valid_order['count'] > 0) {
            $valid = true;
        }
    }
    return $valid;
}

$host = getHost();

// load mustache template engine
use Database\Database;
$db = new Database('orders');

require base_path('../lib/mustache.php-main/src/Mustache/Autoloader.php');
Mustache_Autoloader::register();
$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('views')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('views/partials'))
));

if (!isset($_SESSION['items'])) {
    error_log("order_id missing at do_checkout line " . __LINE__);
    session_destroy();
    echo "<script>window.location.replace('$host/shop?technical_error=true');</script>";
}

$order_to_add_to = false;

if (artprintInCart($db)) {
    $order_to_add_to = getOrderToAddTo($db);
    if (!$order_to_add_to) exit("<script>alert('Orders for artprints are currently only available to customers who have previously ordered a double LP. If there are any left then we will put some signed 2LP versions back in stock ')</script>");
    else {
        $_SESSION['order_to_add_to'] = $order_to_add_to['order_id'];
        $_SESSION['order_no_to_add_to'] = $order_to_add_to['order_no'];
    }
}

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
if ($_SESSION['shipping_method']['shipping_method_id'] == 7) $order_details['shipping_method'] = 7;

if ($_SESSION['shipping_method']['shipping_method_id'] != 1 && $_SESSION['shipping_method']['shipping_method_id'] != 7) {
    [$order_details['totals']['shipping'], $package_id, $package_name] = calculateShipping($db, $_SESSION['rm_zone'], $_SESSION['shipping_method'], $order_details['billing-country']);
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

if (isset($response->error_code)) {
    switch($response->error_code) {
        case "DUPLICATED_CHECKOUT":
            session_destroy();
            error_log(print_r($response, true));
            echo "<script>window.location.replace('$host/shop?technical_error=true');</script>";
    }
}

if (isset($order_details['items']['bundles']['items'])) $items = array_merge($order_details['items']['items'], $order_details['items']['bundles']['items']);
else $items = $order_details['items']['items'];

if (!isset($response->id)) {
    error_log(print_r($response, true));
    exit("Error: Payment not completed");
}

echo $m->render('shop/payment', [
    "checkout_id"=>$response->id,
    "order_id"=>$saved_order['order_id'],
    "name"=>$order_details['name'],
    "items"=>$items,
    "subtotal"=>$order_details['totals']['subtotal'],
    "shipping"=>$order_details['totals']['shipping'],
    "vat"=>$order_details['totals']['vat'],
    "amount"=>$order_details['totals']['total']
]);


function calculateVAT($order_details) {
    return $order_details['totals']['total'] - ($order_details['totals']['total'] / ((100 + VAT_RATE_PC) / 100));
}