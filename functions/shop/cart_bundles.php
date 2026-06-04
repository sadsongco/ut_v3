<?php

if(session_status() === PHP_SESSION_NONE) session_start();

include_once(__DIR__ . "/../../functions/functions.php");
include_once(base_path("functions/shop/get_cart_bundles.php"));
include_once(base_path("functions/shop/calculate_cart_subtotal.php"));

use Database\Database;
$db = new Database('orders');

$checkout = false;
if (isset($_GET['checkout'])) $checkout = true;

$cart_items = getCartBundles($_SESSION['bundles'], $db);
$subtotal = calculateCartSubtotal($cart_items);

echo $m->render('shop/cart_bundles', ["cart_bundles"=>$cart_items, "checkout"=>$checkout, "subtotal"=>$subtotal]);