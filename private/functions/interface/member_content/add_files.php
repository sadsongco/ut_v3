<?php

include(__DIR__ . "/../../../../functions/functions.php");
include(__DIR__ . "/includes/member_content_includes.php");
require_once(base_path("/private/classes/FileUploader.php"));

use Database\Database;
$mem_db = new Database('members');

$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/member_content/')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/member_content/partials/'))
));

use FileUploader\FileUploader;
$uploader = new FileUploader(WEB_ASSET_PATH . MEMBERS_ASSET_PATH, false, ARTICLE_MAX_IMAGE_WIDTH, ARTICLE_THUMBNAIL_WIDTH);
$uploaded_files = $uploader->checkFileSizes()->uploadFiles()->getResponse();

foreach ($uploaded_files as &$uploaded_file) {
    if ($uploaded_file['success']) {
        try {
            $media_id = insertMediaDB($uploaded_file, $mem_db);
            $tag = $uploaded_file['type'] == "images" ? "i" : "a";
            $uploaded_file['tag'] = "{{" . $tag . "::" . $media_id . "}}";
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
    if ($uploaded_file['message'] == $uploaded_file['filename'] . " already exists") {
        [$tag, $media_id] = getMediaID($uploaded_file, $mem_db);
        $uploaded_file['tag'] = "{{" . $tag . "::" . $media_id . "}}";
    }
}

echo $m->render("uploadedFiles", ["uploaded_files"=>$uploaded_files]);

function insertMediaDB ($uploaded_file, $db) {
    $table_name = $uploaded_file['type'] === "images" ? "Content_Images" : "Content_Audio";
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

function getMediaId ($uploaded_file, $db) {
    $ext = pathinfo($uploaded_file['filename'], PATHINFO_EXTENSION);
    if ($ext == "mp3") {
        $table = "Content_Audio";
        $tag = "a";
    } else {
        $table = "Content_Images";
        $tag = "i";
    }
    $query =  "SELECT content_image_id FROM $table WHERE filename = ?";
    $media_id = $db->query($query, [$uploaded_file['filename']])->fetch()['content_image_id'];
    return [$tag, $media_id];
}