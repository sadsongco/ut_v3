<?php

session_start();

// exit($this->renderer->render('shop/tmp_index', ["stylesheets"=>["shop"]]));

use Database\Database;
$db = new Database('orders');

include_once(__DIR__ . "/../../functions/functions.php");
include_once(base_path("functions/shop/get_featured.php"));
include_once(base_path("functions/shop/get_items.php"));
include_once(base_path("functions/shop/get_bundles.php"));
include_once(base_path("functions/shop/get_categories.php"));

$featured = getFeatured($db);
$items = getItems($db);
$bundles = getBundles($db);
$categories = getCategories($db);

echo $this->renderer->render('shop/index', ["featured"=>$featured, "items"=>$items, "bundles"=>$bundles, "categories"=>$categories, "stylesheets"=>["shop"]]);