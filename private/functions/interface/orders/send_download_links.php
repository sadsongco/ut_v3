<?php

include(__DIR__ . "/includes/order_includes.php");
require (base_path("/functions/utility/create_unique_token.php"));
require (base_path("/functions/utility/send_customer_email.php"));
require (base_path("/functions/shop/get_item_data.php"));
//Load Composer's autoloader
require (base_path('../lib/vendor/autoload.php'));

$orders = [];

$query = "SELECT Items.item_id FROM Items WHERE Items.name LIKE '%Rich Inner Life%' AND Items.download IS NOT NULL";

$params = [];
$cond_arr = [];
try {
    $result = $db->query($query)->fetchAll();
    foreach($result as $item) {
        $params[] = $item['item_id'];
        $cond_arr[] = "item_id = ?";
    }
} catch (PDOException $e) {
    echo $e->getMessage();
}

$cond = implode(" OR ", $cond_arr);

$query = "SELECT
    New_Order_items.order_id AS order_db_id
    FROM New_Order_items
    WHERE ($cond)
    AND transaction_id IS NOT NULL
    ORDER BY New_Order_items.order_id DESC";

$order_ids = [];
try {
    $result = $db->query($query, $params)->fetchAll();
    foreach($result as $order) {
        $order_ids[] = trim($order['order_db_id']);
    }
} catch (PDOException $e) {
    echo $e->getMessage();
}

if (file_put_contents(base_path(WEB_ASSET_PATH . DOWNLOAD_ORDER_PATH), implode("\n", $order_ids))) exit("Download orders will send");
else exit("Download orders failed to be written to file");