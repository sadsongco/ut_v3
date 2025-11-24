<?php

include_once(__DIR__ . "/../../functions.php");
include_once(base_path("functions/shop/get_cart_contents.php"));
include_once(base_path("functions/shop/get_package_specs.php"));
include_once(base_path("functions/shop/calculate_cart_subtotal.php"));

include_once(base_path("classes/Database.php"));
use Database\Database;
if (!isset($db)) $db = new Database('orders');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['remove']) || (isset($_POST['update']) && $_POST['quantity'] == 0)) {
    $cart_item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : false;
    $cart_bundle_id = isset($_POST['bundle_id']) ? (int)$_POST['bundle_id'] : false;
    
    if ($cart_item_id) {
        foreach($_SESSION['items'] AS $key=>$cart_item) {
            $item_id = (int)$cart_item['item_id'];
            if ($cart_item['item_id'] == $cart_item_id) {
                unset($_SESSION['items'][$key]);
            }
        }
    }
    
    if ($cart_bundle_id) {
        foreach($_SESSION['bundles'] AS $key=>$cart_bundle) {
            $bundle_id = (int)$cart_bundle['bundle_id'];
            if ($cart_bundle['bundle_id'] == $cart_bundle_id) {
                unset($_SESSION['bundles'][$key]);
            }
        }
    }
    
    header("HX-Trigger: cartUpdated");
    echo "Item removed from cart";
    exit();
}

if (isset($_POST['update'])) {
    $cart_item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : false;
    $cart_bundle_id = isset($_POST['bundle_id']) ? (int)$_POST['bundle_id'] : false;
    
    if ($cart_item_id) {
        foreach($_SESSION['items'] AS $key=>$cart_item) {
            $item_id = (int)$cart_item['item_id'];
            if ($item_id == $cart_item_id) {
                $_SESSION['items'][$key]['quantity'] = (int)$_POST['quantity'];
            }
        }
    }
    
    if ($cart_bundle_id) {
        foreach($_SESSION['bundles'] AS $key=>$cart_bundle) {
            $bundle_id = (int)$cart_bundle['bundle_id'];
            if ($bundle_id == $cart_bundle_id) {
                $_SESSION['bundles'][$key]['quantity'] = (int)$_POST['quantity'];
            }
        }
    }
    $cart_contents = getCartContents($db);
    $subtotal = calculateCartSubtotal($cart_contents);
    $_SESSION['package_specs'] = getPackageSpecs($cart_contents);

    header("HX-Trigger: cartUpdated");
    echo "Cart updated";
    exit(); 
}