<?php

include_once(__DIR__ . "/../../../../functions/functions.php");
include(base_path("private/classes/FileUploader.php"));
include_once('includes/mailout_includes.php');

use FileUploader\FileUploader;
$uploader = new FileUploader(WEB_ASSET_PATH . MAILOUT_ASSET_PATH, false, MAILOUT_MAX_IMAGE_WIDTH, MAILOUT_THUMBNAIL_WIDTH);
$uploaded_files = $uploader->checkFileSizes()->uploadFiles()->getResponse();

$uploaded_file = $uploaded_files[0];
$media_id = addMailoutMediaToDB($uploaded_file, $db);

$tag = $uploaded_file['type'] == "images" ? "i" : "a";
$uploaded_file['tag'] = "<!--{{" . $tag . "::" . $media_id . "}}-->";

echo $m->render('uploadedFile', ["uploaded_file"=>$uploaded_file]);

function addMailoutMediaToDB($uploaded_file, $db) {
    $query = "INSERT INTO MailoutImages (url, caption) VALUES (?, ?);";
    $params = [
        $uploaded_file['filename'],
        $_POST['caption'][$uploaded_file['key']]
    ];
    $db->query($query, $params);
    return $db->lastInsertId();    
}