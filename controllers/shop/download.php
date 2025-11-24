<?php

include_once(__DIR__ . "/../../functions/functions.php");
include_once(base_path("functions/shop/get_categories.php"));
require(base_path("functions/utility/decrypt_token.php"));
require(base_path("functions/utility/create_unique_token.php"));
require(base_path("functions/utility/trigger_download.php"));

use Database\Database;
$db = new Database('orders');

$download_token = isset($paths[2]) ? $paths[2] : false;

if (!$download_token) exit("No Token");

$id = decryptUniqueToken($download_token);
if (!$id || !is_numeric($id)) exit("Invalid Token");

if ($id == 7787782550) {
    $order_no = isset($paths[3]) ? $paths[3] : false;
    if(!$order_no) exit("No Order Number");
    $query = "SELECT Items.download
        FROM New_Orders
        JOIN New_Order_items ON New_Orders.order_id = New_Order_items.order_id
        JOIN Items ON New_Order_items.item_id = Items.item_id
        WHERE New_Orders.order_id = ?";
    $item = $db->query($query, [$order_no])->fetch();
    $file_path = base_path(DOWNLOAD_PATH . $item['download']);
    triggerDownload($item['download'], $file_path);
    exit();
}

$query = "SELECT download
    FROM Download_tokens
    JOIN Items ON Items.item_id = Download_tokens.item_id
    WHERE download_token_id = ?";
$item = $db->query($query, [$id])->fetch();
$filename = isset($item['download']) ? $item['download'] : false;
if (!$filename) exit("Invalid Token");

$file_path = base_path(DOWNLOAD_PATH . $filename);

triggerDownload($filename, $file_path);

$query = "DELETE FROM Download_tokens WHERE download_token_id = ?";
$db->query($query, [$id]);
