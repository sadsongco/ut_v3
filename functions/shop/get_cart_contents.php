<?php

include_once(__DIR__ . "/../../functions/functions.php");
include_once(base_path("functions/shop/get_cart_items.php"));
include_once(base_path("functions/shop/get_cart_bundles.php"));

function getCartContents($db)
{
    $bundles = isset($_SESSION['bundles']) && sizeof($_SESSION['bundles']) > 0 ? getCartBundles($_SESSION['bundles'], $db) : [];
    $cart_items = isset($_SESSION['items']) && sizeof($_SESSION['items']) > 0 ? getCartItems($_SESSION['items'], $db) : [];
    if (sizeof($cart_items) == 0 && sizeof($bundles) == 0) return false;
    return ["items"=>$cart_items, "bundles"=>$bundles];
}

