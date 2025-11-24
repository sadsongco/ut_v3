<?php

include(__DIR__ . "/../../functions.php");
include(base_path("classes/Database.php"));

use Database\Database;
$db = new Database('orders');
$query = "SELECT
    Items.price,
    Item_options.option_price
FROM Items
JOIN Item_options
    ON Item_options.item_option_id = ?
WHERE Items.item_id = ?";
$item_prices = $db->query($query, [$_GET['option_id'], $_GET['item_id']])->fetch();

$item_price = $item_prices['option_price'] ?? $item_prices['price'];

echo "&pound;" . number_format($item_price, 2);