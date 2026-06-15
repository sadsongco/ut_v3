<?php

include(__DIR__ . "/../../../../functions/functions.php");

use Database\Database;
$db = new Database('content');

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
