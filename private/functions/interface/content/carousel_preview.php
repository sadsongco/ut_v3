<?php

include(__DIR__ . "/../../../../functions/functions.php");
include(base_path("classes/Database.php"));
include(base_path("../lib/mustache.php-main/src/Mustache/Autoloader.php"));

use Database\Database;
$db = new Database('content');

Mustache_Autoloader::register();
$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('views/')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('views/partials/'))
));

$query = "SELECT * FROM carousel ORDER BY tile_order ASC";
$carousel_tiles = $db->query($query)->fetchAll();

foreach ($carousel_tiles as &$tile) {
    $tile['path'] = "serve/" . CAROUSEL_ASSET_PATH . "images/" . str_replace(".", "/", $tile['img_url']);
}
echo $m->render('carousel', ['carousel_tiles'=>$carousel_tiles]);
