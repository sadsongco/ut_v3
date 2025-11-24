<?php

include(__DIR__ . "/../../../../functions/functions.php");
include(base_path("private/functions/utility/nl2p.php"));
include(base_path("classes/Database.php"));
include(base_path("private/classes/FileUploader.php"));
include(base_path("../lib/mustache.php-main/src/Mustache/Autoloader.php"));
Mustache_Autoloader::register();
$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/content/')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/content/partials/'))
));

use Database\Database;
$db = new Database('admin');

use FileUploader\FileUploader;

if (isset($_POST['create'])) {
    $uploader = new FileUploader(WEB_ASSET_PATH . CAROUSEL_ASSET_PATH, false, CAROUSEL_MAX_IMAGE_WIDTH, CAROUSEL_THUMBNAIL_WIDTH);
    $uploaded_files = $uploader->checkFileSizes()->uploadFiles()->getResponse();
    $uploaded_file = $uploaded_files[0];
    
    if ($uploaded_file["success"]) {
        $media_id = createCarouselDB($uploaded_file, $db);
    }
    header("HX-Trigger: carouselUpdated");
    echo $m->render("carouselForm", ["uploaded_file"=>$uploaded_file]);
}

if (isset($_POST['update'])) {
    $post_arr = rearrayCarouselPost();
    foreach ($post_arr as $post) {
        updateCarouselDB($post, $db);
    }
    $carousel_tiles = getCarouselTiles($db);
    echo $m->render("carouselEdit", ["carousel_tiles"=>$carousel_tiles, "updated"=>true]);
}

if (isset($_POST['delete'])) {
    unlink(base_path(WEB_ASSET_PATH . CAROUSEL_ASSET_PATH . "images/" . $_POST['img_url']));
    unlink(base_path(WEB_ASSET_PATH . CAROUSEL_ASSET_PATH . "images/thumbnail/" . $_POST['img_url']));
    $query = "DELETE FROM carousel WHERE carousel_id = ?;";
    $params = [$_POST['carousel_id']];
    $db->query($query, $params);
}


function createCarouselDB($uploaded_file, $db) {
    $query = "INSERT INTO carousel (img_url, tile_title, tile_text) VALUES (?, ?, ?);";
    $params = [
        $uploaded_file['filename'],
        $_POST['tile_title'][$uploaded_file['key']],
        $_POST['tile_text'][$uploaded_file['key']]
    ];
    $db->query($query, $params);
    return $db->lastInsertId();
}

function rearrayCarouselPost() {
    $arr = [];
    foreach ($_POST['carousel_id'] as $key => $value) {
        $arr[] = [
            'carousel_id'=>$value,
            'tile_title'=>$_POST['title'][$key],
            'tile_text'=>$_POST['text'][$key],
            'tile_order'=>$_POST['tile_order'][$key]
        ];
    }
    return $arr;
}

function updateCarouselDB($post, $db) {
    $query = "UPDATE carousel SET tile_title = ?, tile_text = ?, tile_order = ? WHERE carousel_id = ?;";
    $params = [
        $post['tile_title'],
        $post['tile_text'],
        $post['tile_order'],
        $post['carousel_id']
    ];
    $db->query($query, $params);
}

function getCarouselTiles($db) {
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

    return $carousel_tiles;
}