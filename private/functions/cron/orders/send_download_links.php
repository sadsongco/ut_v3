<?php

include(__DIR__ . "/../../interface/orders/includes/order_includes.php");
require (base_path("/functions/utility/create_unique_token.php"));
require (base_path("/functions/utility/send_customer_email.php"));
require (base_path("/functions/shop/get_item_data.php"));
//Load Composer's autoloader
require (base_path('../lib/vendor/autoload.php'));

$orders_str = file_get_contents(base_path(WEB_ASSET_PATH . DOWNLOAD_ORDER_PATH));

$orders = explode("\n", $orders_str);
if (sizeof($orders) == 0 || $orders[0] == "") exit();

$order_db_id = array_pop($orders);

file_put_contents(base_path(WEB_ASSET_PATH . DOWNLOAD_ORDER_PATH), implode("\n", $orders));
try {
    $order = getDownloadOrder($order_db_id, $db);
    sendCustomerEmail($order, "download", $db, $m);
} catch (Exception $e) {
    echo  "Couldn't update order " . $order_db_id . ": " . $e->getMessage();
}


function getDownloadOrder($order_db_id, $db) {
    try {
        $query = "SELECT
            CONCAT(DATE_FORMAT(New_Orders.order_date, '%y%m%d'), '-', New_Orders.order_id) AS order_no,
            New_Orders.order_id,
            Customers.name,
            Customers.customer_id,
            Customers.email
        FROM New_Orders
        JOIN Customers ON New_Orders.customer_id = Customers.customer_id
        WHERE New_Orders.order_id = ?";
        $order = $db->query($query, [$order_db_id])->fetch();
        $order['order_db_id'] = $order_db_id;
        $order['download_items'] = getDownloadItems($order['order_db_id'], $db);
        foreach ($order['download_items'] as &$item) {
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
    } catch (PDOException $e) {
        throw new Exception($e);
    }
    return $order;
}

function getDownloadItems($order_db_id, $db) {
    $items = [];
    $query = "SELECT
        Items.name,
        DATE_FORMAT(Items.release_date, '%D %M %Y') AS disp_release_date,
        Items.item_id,
        Items.download
    FROM New_Order_items
    JOIN Items ON New_Order_items.item_id = Items.item_id
    AND Items.download IS NOT NULL
    WHERE New_Order_items.order_id = ?";
    $params = [$order_db_id];
    $items = $db->query($query, $params)->fetchAll();
    return $items;
}