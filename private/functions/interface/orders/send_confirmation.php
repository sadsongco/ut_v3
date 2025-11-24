<?php

include(__DIR__ . "/includes/order_includes.php");
require (base_path("/functions/utility/create_unique_token.php"));
require (base_path("/functions/utility/send_customer_email.php"));
require (base_path("/functions/utility/create_order_pdf.php"));
require (base_path("/functions/shop/get_item_data.php"));
require (base_path("/functions/shop/make_order_pdf.php"));
//Load Composer's autoloader
require (base_path('../lib/vendor/autoload.php'));

if (!isset($_GET['order_id'])) exit("No Order ID");

$order_db_id = $_GET['order_id'];

$shipping_items = [];
$download_items = [];
$preorder_items = [];
$shipping_all = false;

$query = "SELECT *,
            DATE_FORMAT(New_Orders.dispatched, '%a %D %M, %Y') AS dispatched
        FROM New_Orders WHERE order_id = ?";
$order = $db->query($query, [$order_db_id])->fetch();
$order['items'] = getOrderItems($order, $db);

foreach ($order['items'] AS $item) {
    updateItemData($item, $db);
    classifyItem($item, $order_db_id, $db, $shipping_items, $download_items, $preorder_items, $order['dispatched']);
}

$order['download_items'] = $download_items;
$order['preorder_items'] = $preorder_items;

if (!empty($shipping_items) && empty($preorder_items)) $shipping_all = true;
if (empty($shipping_items) && !empty($preorder_items) && $order['dispatched']) $shipping_all = ["dispatched"=>$order['dispatched']];
if (!empty($shipping_items) && !empty($preorder_items)) $preorder_items['held'] = [...$shipping_items];

try {
    $query = "SELECT
            CONCAT(DATE_FORMAT(New_Orders.order_date, '%y%m%d'), '-', New_Orders.order_id) AS order_id,
            DATE_FORMAT(New_Orders.dispatched, '%a %D %M, %Y') AS dispatched,
            New_Orders.rm_tracking_number,
            Shipping_methods.service_name,
            Customers.name,
            Customers.email
        FROM New_Orders
        JOIN Customers ON New_Orders.customer_id = Customers.customer_id
        JOIN Shipping_methods ON New_Orders.shipping_method = Shipping_methods.shipping_method_id
        WHERE New_Orders.order_id = ?";
    $order = $db->query($query, [$order_db_id])->fetch();

    $order['shipping_items'] = $shipping_all;
    $order['download_items'] = $download_items;
    $order['preorder_items'] = $preorder_items;

    sendCustomerEmail($order, "success", $db, $m);
}
catch (Exception $e) {
    echo $e->getMessage();
    exit();
}

echo "Confirmation email sent to " . $order['email'];