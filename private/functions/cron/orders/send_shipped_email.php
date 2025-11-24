<?php

include(__DIR__ . "/../../interface/orders/includes/order_includes.php");
require (base_path("/functions/utility/send_customer_email.php"));
//Load Composer's autoloader
require base_path('../lib/vendor/autoload.php');
require base_path('functions/shop/get_item_data.php');
require base_path('functions/utility/create_unique_token.php');

$orders_str = file_get_contents(base_path(WEB_ASSET_PATH . SHIPPED_LIST_PATH));

$orders = explode("\n", $orders_str);
if (sizeof($orders) == 0 || $orders[0] == "") exit();

$order_db_id = array_pop($orders);

file_put_contents(base_path(WEB_ASSET_PATH . SHIPPED_LIST_PATH), implode("\n", $orders));

try {
    $order_array = createOrderArray($order_db_id, $db);
    $order_array['items'] = getOrderItems($order_array, $db);
    $shipping_items = [];
    $download_items = [];
    $preorder_items = [];
    $shipping_all = false;
    foreach ($order_array['items'] as &$item) {
        updateItemData($item, $db);
        classifyItem($item, $order_array['order_id'], $db, $shipping_items, $download_items, $preorder_items);
    }
    $order_array['shipping_items'] = $shipping_all;
    $order_array['download_items'] = $download_items;
    $shipped_display_date = strtotime($order_array['dispatched']);
    $order_array['shipped_on'] = date("jS F Y", $shipped_display_date);
    sendCustomerEmail($order_array, "shipped", $db, $m);
} catch (PDOException $e) {
    echo  "Couldn't send email for shipped order " . $order_db_id . ": " . $e->getMessage();
}

function createOrderArray($order_id, $db) {
    try {
        $query = "SELECT
            CONCAT(DATE_FORMAT(New_Orders.order_date, '%y%m%d'), '-', New_Orders.order_id) AS order_no,
            New_Orders.order_id,
            New_Orders.subtotal,
            New_Orders.shipping,
            New_Orders.vat,
            New_Orders.total,
            New_Orders.order_date,
            New_Orders.dispatched,
            New_Orders.rm_tracking_number,
            New_Orders.mg,
            Customers.name,
            Customers.address_1,
            Customers.address_2,
            Customers.city,
            Customers.postcode,
            Customers.email,
            Countries.name AS country
        FROM New_Orders
        JOIN Customers ON New_Orders.customer_id = Customers.customer_id
        JOIN Countries ON Customers.country = Countries.country_id
        WHERE New_Orders.order_id = ?";
        $order = $db->query($query, [$order_id])->fetch();
    } catch (PDOException $e) {
        throw new PDOException($e);
    }
    return $order;
}