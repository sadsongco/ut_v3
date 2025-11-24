<?php

session_start();

include_once(__DIR__ . "/../../functions/functions.php");
include_once(base_path("functions/shop/get_cart_contents.php"));
include_once(base_path("functions/shop/calculate_cart_subtotal.php"));

require base_path('classes/Database.php');

use Database\Database;
$db = new Database('orders');

if (!isset($m)) {
    // load mustache for all controllers
    require base_path('../lib/mustache.php-main/src/Mustache/Autoloader.php');
    Mustache_Autoloader::register();
    $m = new Mustache_Engine(array(
        'loader' => new Mustache_Loader_FilesystemLoader(base_path('views')),
        'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('views/partials'))
    ));
}

$checkout = false;
if (isset($_GET['checkout'])) $checkout = true;

$cart_contents = getCartContents($db);
$subtotal = false;
if ($cart_contents)
    $subtotal = calculateCartSubtotal($cart_contents);

echo $m->render('shop/cart_items', ["cart_items"=>$cart_contents, "checkout"=>$checkout, "subtotal"=>$subtotal]);