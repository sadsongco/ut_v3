<?php

include(__DIR__ . "/../../../../functions/functions.php");
require_once(base_path("classes/Database.php"));
require_once(base_path("/private/classes/FileUploader.php"));

use Database\Database;
$db = new Database('admin');

// templating
require base_path('../lib/mustache.php-main/src/Mustache/Autoloader.php');
Mustache_Autoloader::register();

$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/content/')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/content/partials/'))
));

use FileUploader\FileUploader;
$uploader = new FileUploader(WEB_ASSET_PATH . ARTICLE_ASSET_PATH, false, ARTICLE_MAX_IMAGE_WIDTH, ARTICLE_THUMBNAIL_WIDTH);
$uploaded_files = $uploader->checkFileSizes()->uploadFiles()->getResponse();

foreach ($uploaded_files as &$uploaded_file) {
    if ($uploaded_file['success']) {
        try {
            $media_id = insertMediaDB($uploaded_file, $db);
            $tag = $uploaded_file['type'] == "images" ? "i" : "a";
            $uploaded_file['tag'] = "{{" . $tag . "::" . $media_id . "}}";
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
}

echo $m->render("uploadedFiles", ["uploaded_files"=>$uploaded_files]);

function insertMediaDB ($uploaded_file, $db) {
    $table_name = $uploaded_file['type'] === "images" ? "images" : "media";
    try {
        $query = "INSERT INTO $table_name (title, filename, notes)VALUES (?, ?, ?);";
        $params = [
            $_POST["title"][$uploaded_file['key']],
            $uploaded_file['filename'],
            $_POST["notes"][$uploaded_file['key']]
        ];
        $db->query($query, $params);
        return $db->lastInsertId();
    }
    catch (PDOException $e) {
        throw new Exception($e->getMessage());
    }
}