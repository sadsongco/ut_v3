<?php

include_once(base_path("functions/shop/get_item_data.php"));

/**
 * Given a database connection, retrieve all items in the shopping cart.
 *
 * @param array $items array of cart item ids and option ids
 * @param Class $db
 * @return array       associative array of cart items
 */
function getCartItems($items, $db, $details=true)
{
    $item_details = $details ? "Items.*" : "Items.item_id, Items.image, Items.price";
    $cart_items = [];
    foreach ($items AS $item) {
        // p_2($item);
        $cart_items[] = getItemData($item, $item_details, $db);
    }
    return $cart_items;
}

