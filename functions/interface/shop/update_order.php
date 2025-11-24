<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['order_id'])) exit(); // no order id, nothing we can do here

if (!defined('ENV')) include_once(__DIR__ ."/../../../functions/functions.php");
require_once(base_path("classes/Database.php"));
use Database\Database;

if (!isset($db)) $db = new Database('orders');

$order_id = explode("-",$_SESSION['order_id'])[1];


if (isset($_POST['status']) && $_POST['status'] == 'FAILED') {
    try {
        $db->beginTransaction();
        $query = "DELETE FROM New_Orders WHERE order_id = ?";
        $db->query($query, [$order_id]);
        if (isset($_SESSION['items'])) {
            foreach($_SESSION['items'] AS $item) {
                returnStock($item, $db);
            }
        }
        if (isset($_SESSION['bundles'])) {
            foreach($_SESSION['bundles'] AS $bundle) {
                foreach($bundle['items'] AS $item) {
                    $item['quantity'] = $bundle['quantity'];
                    returnStock($item, $db);
                }
            }
        }
        $db->commit();
        $response = [
            'status' => 'failed',
            'response'=> $_POST
        ];
        unset($_SESSION['order_id']);
        echo json_encode($response);
        exit();
    }
    catch (Exception $e) {
        $db->rollback();
        error_log($e);
        exit();
    }
}


$query = "UPDATE New_Orders SET transaction_id = ? WHERE order_id = ?";
try {
    $db->query($query, [$_POST['transaction_id'], $order_id]);
}
catch (Exception $e) {
    error_log($e);
}

$response = [
    'status' => 'success',
    'response'=> $_POST
];

echo json_encode($response);

function returnStock($item, $db)
{
    if (isset($item['option_id']) && $item['option_id']) {
        $query = "UPDATE Item_options SET option_stock = option_stock + ? WHERE item_option_id = ?";
        $params = [$item['quantity'], $item['option_id']];
        $db->query($query, $params);
    } else {
        $query = "UPDATE Items SET stock = stock + ? WHERE item_id = ?";
        $params = [$item['quantity'], $item['item_id']];
        $db->query($query, $params);
    }
}