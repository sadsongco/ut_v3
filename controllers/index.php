<?php

include(base_path("private/functions/utility/nl2p.php"));

use Database\Database;

$db = new Database();

$background = [
    "ril",
    "cb",
    "sol",
    "sd",
    "ygi",
    "r1",
    "ct",
    "st"
];

$background_choice = $background[array_rand($background)];

$carousel_tiles = $db->query("SELECT img_url, tile_title, tile_text FROM carousel ORDER BY tile_order ASC")->fetchAll();
foreach ($carousel_tiles as &$tile) {
    $tile['path'] = "serve/" . CAROUSEL_ASSET_PATH . "images/" . str_replace(".", "/", $tile['img_url']);
    $tile['tile_text'] = nl2p($tile['tile_text']);
}

if (isset($_GET['article_id'])) header("Location: /blog?article_id=" . $_GET['article_id']);

$modules = ["emailList"];

$env = false;
if (ENV === 'dev') $env = 'dev';

echo $this->renderer->render('index', [
    'background'=>$background_choice,
    'carousel_tiles'=>$carousel_tiles,
    "nav"=>$this->nav,
    "socials"=>true,
    "modules"=>$modules,
    "env"=>$env
]);

