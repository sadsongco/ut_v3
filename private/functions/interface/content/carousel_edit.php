<?php

include(__DIR__ . "/../../../../functions/functions.php");
include(base_path("classes/Database.php"));
include(base_path("../lib/mustache.php-main/src/Mustache/Autoloader.php"));

use Database\Database;
$db = new Database('content');

Mustache_Autoloader::register();
$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/content/')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/content/partials/'))
));

$query = "SELECT * FROM carousel ORDER BY tile_order ASC";
$carousel_tiles = $db->query($query)->fetchAll();

$order_options = [];
for ($a = 1; $a <= sizeof($carousel_tiles); $a++) {
    $order_options[] = ["order"=>$a];
}

foreach ($carousel_tiles as &$tile) {
    $tile['order_options'] = $order_options;
    $tile['order_options'][$tile['tile_order']-1] = ["order"=>$tile['tile_order'], "selected"=>"selected"];
    $tile['path'] = "/serve/" . CAROUSEL_ASSET_PATH . "images/" . str_replace(".", "/", $tile['img_url']);
}

// p_2($carousel_tiles);


echo $m->render('carouselEdit', ['carousel_tiles'=>$carousel_tiles]);
