<?php

session_start();

include_once(__DIR__ . "/../../functions/functions.php");
include_once(base_path("functions/shop/get_categories.php"));

use Database\Database;
$db = new Database('orders');

$query = "SELECT *
    FROM Items
    WHERE item_id = ?";

$item = $db->query($query, [$paths[2]])->fetch();

if ($item['image'] == "") unset($item['image']);
else $item['image_path'] = "/serve/" . SHOP_ASSET_PATH . "images/" . str_replace(".", "/", $item['image']);

$item_options = $db->query("SELECT * FROM Item_options WHERE item_id = ? AND Item_options.option_stock > 0", [$paths[2]])->fetchAll();

$item['option'] = sizeof($item_options) > 0 ? ['options'=>$item_options] : false;

$categories = getCategories($db);

echo $this->renderer->render('shop/item', ["item"=>$item, "categories"=>$categories, "stylesheets"=>["shop"]]);