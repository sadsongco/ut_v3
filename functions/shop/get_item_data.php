<?php

function getItemData($item, $item_details, $db)
{
        if (isset($item['option_id']) && $item['option_id']) {
            $query = "SELECT
                $item_details,
                Item_options.option_stock as stock,
                Item_options.item_option_id as option_id,
                Item_options.option_name,
                Item_options.option_price,
                Item_options.option_weight,
                Items.release_date,
                Items.e_delivery,
                Items.packaging_classification
            FROM Items
            JOIN Item_options ON Item_options.item_option_id = ?
            WHERE Items.item_id = ?";
            $params = [$item['option_id'], $item['item_id']];
        } else {
            $query = "SELECT $item_details FROM Items WHERE item_id = ?";
            $params = [$item['item_id']];
        }
        $cart_item = $db->query($query, $params)->fetch();
        if (isset($cart_item['option_price']) && $cart_item['option_price']) $cart_item['price'] = number_format($cart_item['option_price'], 2);
        if ($cart_item['image'] == "") unset($cart_item['image']);
        else $cart_item['image_path'] = "/serve/" . SHOP_ASSET_PATH . "images/" . str_replace(".", "/", $cart_item['image']);

        return [...$cart_item, "quantity"=>$item['quantity']]; // add quantity to $cart_item;
}

function classifyItem(&$item, $order_db_id, $db, &$shipping_items, &$download_items, &$preorder_items, $dispatched=false)
{
    if ($dispatched) $item['dispatched'] = $dispatched;
    if (isset($item['release_date']) && $item['release_date'] > date("Y-m-d")) {
        if (isset($item['download']) && $item['download'] != "") {
            $download_items[] = ["download"=>$item['download'], "disp_release_date"=>$item['disp_release_date'], "name"=>$item['name']];
        }
        if ($item['e_delivery']) $preorder_items["e_delivery"][] = $item;
        else $preorder_items['shipping'][] = $item;
        return;
    }
    if (!$item['e_delivery']) {
        $shipping_items[] = $item;
    }
    if ($item['download']) {
        $query = "SELECT download_token_id FROM Download_tokens WHERE order_id = ? AND item_id = ?";
        $res = $db->query($query, [$order_db_id, $item['item_id']])->fetch();
        if (isset($res['download_token_id'])) {
            $download_token = createUniqueToken($res['download_token_id']);
        }
        else {
            $query = "INSERT INTO Download_tokens (order_id, item_id) VALUES (?, ?)";
            $db->query($query, [$order_db_id, $item['item_id']]);
            $download_token = createUniqueToken($db->lastInsertId());
        }
        $item["download_token"] = $download_token;
        $download_items[] = ["download"=>$item['download'], "download_token"=>$download_token, "disp_release_date"=>$item['disp_release_date'], "name"=>$item['name']];
    }
}

function updateItemData(&$item, $db)
{
    $query = "SELECT name, e_delivery, download, release_date, DATE_FORMAT(release_date, '%D %M %Y') AS disp_release_date FROM Items WHERE item_id = ?";
    $res = $db->query($query, [$item['item_id']])->fetch();
    $item['name'] = $res['name'];
    $item['e_delivery'] = $res['e_delivery'];
    $item['download'] = $res['download'];
    $item['release_date'] = $res['release_date'];
    $item['disp_release_date'] = $res['disp_release_date'];
    if ($item['option_id']) {
        $query = "SELECT option_name FROM Item_options WHERE item_option_id = ?";
        $res = $db->query($query, [$item['option_id']])->fetch();
        $item['option_name'] = $res['option_name'];
    }
}
