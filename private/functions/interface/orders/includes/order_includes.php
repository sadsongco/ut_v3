<?php

include(__DIR__ . "/../../../../../functions/functions.php");
include(base_path("classes/Database.php"));
include(base_path("../lib/mustache.php-main/src/Mustache/Autoloader.php"));

use Database\Database;

$db = new Database('orders');

Mustache_Autoloader::register();

$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/orders')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/orders/partials'))
));

function getOrderItems($order, $db) {
    try {
        $query = "SELECT
            New_Order_items.order_price,
            New_Order_items.quantity,
            New_Order_items.option_id,
            Items.*
        FROM New_Order_items
        JOIN Items ON New_Order_items.item_id = Items.item_id
        WHERE New_Order_items.order_id = ?";
        $params = [$order["order_id"]];
        return $db->query($query, $params)->fetchAll();
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
}