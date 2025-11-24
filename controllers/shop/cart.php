<?php

session_start();

include_once(__DIR__ . "/../../functions/functions.php");
include_once(base_path("functions/shop/get_categories.php"));
include_once(base_path("functions/shop/get_cart_contents.php"));
include_once(base_path("functions/shop/calculate_cart_subtotal.php"));

use Database\Database;
$db = new Database('orders');

$categories = getCategories($db);

if (!isset($_SESSION['bundles']) && (!isset($_SESSION['items']) || sizeof($_SESSION['items']) == 0)) {
    echo $this->renderer->render('shop/cart', ["empty"=>true, "categories"=>$categories, "stylesheets"=>["shop"]]);
    exit();
}

$cart_contents = getCartContents($db);
$subtotal = calculateCartSubtotal($cart_contents);

echo $this->renderer->render('shop/cart', ["cart_contents"=>$cart_contents, "categories"=>$categories, "subtotal"=>$subtotal,"stylesheets"=>["shop"]]);
