<?php

session_start();

include_once(__DIR__ . "/../../functions.php");


if (isset($_POST['is_bundle'])) {
    $items = rearrayBundleItems($_POST);
    unset($_POST['item_id']);
    unset($_POST['option_id']);
    if (!isset($_SESSION['bundles']) || sizeof($_SESSION['bundles']) == 0) {
        $_SESSION['bundles'][] = ['bundle_id'=>$_POST['bundle_id'], 'items'=>$items, 'quantity'=>1];
    } else {
        $bundle_updated = false;
        foreach ($_SESSION['bundles'] AS &$bundle) {
            if ($bundle['bundle_id'] == $_POST['bundle_id']) {
                $bundle['quantity']++;
                $bundle_updated = true;
                break;
            }
        }
        if (!$bundle_updated) $_SESSION['bundles'][] = ['bundle_id'=>$_POST['bundle_id'], 'items'=>$items, 'quantity'=>1];
    }
}

if (isset($_POST['item_id'])) {
    $option_id = isset($_POST['option_id']) && $_POST['option_id'] != "" ? (int)$_POST['option_id'] : false;
    
    if (!isset($_SESSION['items']) || sizeof($_SESSION['items']) == 0) {
        $_SESSION['items'][] = ['item_id'=>$_POST['item_id'], 'option_id'=>$option_id, 'quantity'=>1];
    } else {
        $item_updated = false;
        foreach ($_SESSION['items'] AS &$item) {
            if ($item['item_id'] == $_POST['item_id'] && $item['option_id'] == $option_id) {
                $item['quantity']++;
                $item_updated = true;
                break;
            }
        }
        if (!$item_updated) $_SESSION['items'][] = ['item_id'=>$_POST['item_id'], 'option_id'=>$option_id, 'quantity'=>1];
    }
}

header("HX-Trigger: cartUpdated");
echo 'Item added to cart<br><a class="cart-button" href="/shop/cart">View Cart</a>';

function rearrayBundleItems($bundle) {
    $items = [];
    foreach ($bundle['item_id'] AS $key => $value) {
        $items[] = ['item_id'=>$value, 'option_id'=>$bundle['option_id'][$key]];
    }
    return $items;
}