<?php

session_start();

include_once(__DIR__ . "/../../functions/functions.php");
include_once(base_path("functions/shop/get_cart_contents.php"));
include_once(base_path("functions/shop/get_package_specs.php"));
include_once(base_path("functions/shop/get_shipping_methods.php"));
include_once(base_path("functions/shop/calculate_cart_subtotal.php"));
include_once(base_path("functions/interface/shop/calculate_shipping.php"));

use Database\Database;
if (!isset($db)) $db = new Database('orders');

$countries = $db->query('SELECT * FROM `Countries` ORDER BY `name` ASC')->fetchAll();

if ((!isset($_SESSION['bundles']) || sizeof($_SESSION['bundles']) == 0) && (!isset($_SESSION['items']) || sizeof($_SESSION['items']) == 0)) {
    header("Location: /shop/");
    exit();
}

$default_zone = 'UK';
$_SESSION['zone'] = $default_zone;
$_SESSION['rm_zone'] = $default_zone;

$cart_contents = getCartContents($db);
$subtotal = calculateCartSubtotal($cart_contents);
$_SESSION['subtotal'] = $subtotal;

$_SESSION['package_specs'] = getPackageSpecs($cart_contents);
$shipping_options = [
    "shipping_method_id" => 1,
    "service_name" => "Digital Download",
    "service_code" => "E_DEL"
];
$_SESSION['shipping_method'] = $shipping_options;
$_SESSION['shipping'] = $shipping = 0;
$shipping_disp = "0.00";

if (!isset($_SESSION['package_specs']['e_delivery'])) {
    $shipping_options = getShippingMethods($default_zone, $db);
    if (sizeof($shipping_options) == 0) {
        exit("Sorry, no shipping options available.");
    }
    $default_method = $shipping_options[0];
    $_SESSION['shipping_method'] = $default_method;
    
    [$shipping, $package_id, $package_name] = calculateShipping($db, $default_zone, $default_method);
    $_SESSION['shipping'] = round($shipping, 2);
    $shipping_disp = number_format($shipping, 2);
}

$total = $subtotal + $shipping;
$_SESSION['total'] = $total;
$total_disp = number_format($total, 2);

echo $this->renderer->render('shop/checkout', ["cart_items"=>$cart_contents, "subtotal"=>$subtotal, "shipping_options"=>$shipping_options, "shipping"=>$shipping_disp, "total"=>$total_disp, "countries"=>$countries, "stylesheets"=>["shop"]]);
