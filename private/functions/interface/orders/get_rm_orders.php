<?php

include_once(__DIR__."/includes/order_includes.php");
require (base_path("/functions/utility/send_customer_email.php"));
//Load Composer's autoloader
require base_path('../lib/vendor/autoload.php');
require base_path('functions/shop/get_item_data.php');
require base_path('functions/utility/create_unique_token.php');

$unsent_orders = getUnsentNew_Orders($db);
if (empty($unsent_orders)) {
    exit("No orders to update.");
}

$unsent_orders_arr = [];
echo "Querying Royal Mail for order nos: <br>";
foreach ($unsent_orders as $unsent_order) {
    echo $unsent_order["rm_order_identifier"] . "<br>";
    $unsent_orders_arr[] = $unsent_order["rm_order_identifier"];
}

$unsent_orders_string = implode(";", $unsent_orders_arr) . "/";

$url = $path = RM_BASE_URL."/orders/" . $unsent_orders_string;

$headers = [
    "Authorization: " . RM_API_KEY,
    "Content-Type: application/json"
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $path);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
$responseObj = json_decode($response);

$output = '<div class="pickingList">';

$shipped_arr = [];

foreach ($responseObj as $order) {
    $output .= '<div class="pickingListRow">';
    if (isset($order->code)) {
        $output .= "Error: " . $order->code . ": " . $order->message . "<br>";
        $output .= '</div>';
        continue;
    }
    if (!isset($order->orderReference)) {
        $output .= "No order reference for royal mail id " . $order->orderIdentifier . "<br>";
        $output .= '</div>';
        continue;
    }
    $shippedOn = isset($order->shippedOn) ? $order->shippedOn : NULL;
    if (!$shippedOn) {
        $output .= "Order " . $order->orderReference . " not marked dispatched on Royal Mail portal.<br>";
        $output .= '</div>';
        continue;
    }
    if (!isset($order->trackingNumber)) {
        $output .= "No tracking number for order " . $order->orderReference . " - DB will update<br>";
        $output .= '</div>';
    }
    $query = "SELECT order_id FROM New_Orders WHERE order_id = ? AND dispatched IS NULL";
    $check = $db->query($query, [$order->orderReference])->fetch();
    if (!$check) {
        $output .= "Order " . $order->orderReference . " not found in database.<br>";
        $output .= '</div>';
        continue;
    }
    
    try {
        updateOrderWithRMData($shippedOn, $order, $db);
        $shipped_arr[] = $order->orderReference;
        $output .=  "Updated order " . $order->orderReference . "<br>";
    } catch (PDOException $e) {
        $output .=  "Couldn't update order " . $order->orderReference . ": " . $e->getMessage();
    }
    $output .= '</div>';
}
$output .= "</div>";

if (file_put_contents(base_path(WEB_ASSET_PATH . SHIPPED_LIST_PATH), implode("\n", $shipped_arr) . "\n", FILE_APPEND)) $output .= "Shipped orders written to file";
else $output .= "Shipped orders failed to be written to file";

header ('HX-Trigger:updateOrderList');
echo $output;

function getUnsentNew_Orders($db) {
    $query = "SELECT rm_order_identifier FROM New_Orders
        WHERE transaction_id IS NOT NULL
        AND dispatched IS NULL
        AND rm_order_identifier IS NOT NULL
    ORDER BY order_id ASC
    LIMIT 100";
    return $db->query($query)->fetchAll();
}

function updateOrderWithRMData($shippedOn, $order, $db) {
    try {
        $query = "UPDATE New_Orders
        SET
        `dispatched` = ?,
        `rm_order_identifier` = ?,
        `rm_created` = ?,
        `rm_tracking_number` = ?
        WHERE `order_id` = ?";
        $params = [
            $shippedOn,
            (int)$order->orderIdentifier,
            $order->createdOn,
            $order->trackingNumber,
            (int)$order->orderReference
        ];
        $db->query($query, $params);
    } catch (PDOException $e) {
        throw new PDOException($e);
    }
}
