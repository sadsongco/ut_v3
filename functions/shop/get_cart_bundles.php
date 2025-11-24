<?php

include_once(base_path("functions/shop/get_item_data.php"));

function getCartBundles($bundles, $db, $details=true) {
    $item_details = $details ? "Items.*" : "Items.item_id, Items.image, Items.price";
    $cart_bundles = [];
    foreach($bundles as $cart_bundle) {
        $query = "SELECT bundle_id, price FROM Bundles WHERE bundle_id = ?";
        $bundle = $db->query($query, [$cart_bundle['bundle_id']])->fetch();
        $bundle['bundle_stock'] = 99;
        foreach($cart_bundle['items'] as &$item) {
            $item['quantity'] = $cart_bundle['quantity'];
            $item_data = getItemData($item, $item_details, $db);
            if ($item_data['stock'] < $bundle['bundle_stock']) $bundle['bundle_stock'] = $item_data['stock'];
            $bundle['items'][] = $item_data;
        }
        $bundle['price'] = number_format($bundle['price'], 2);
        $cart_bundles[] = [...$bundle, "quantity"=>$cart_bundle['quantity']];
    }
    return $cart_bundles;
}