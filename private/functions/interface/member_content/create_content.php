<?php
include(__DIR__ . "/../../../../functions/functions.php");
include(__DIR__ . "/includes/member_content_includes.php");
require_once(base_path("/private/classes/FileUploader.php"));
use FileUploader\FileUploader;

if (!empty($_FILES)) {
    $uploader = new FileUploader(WEB_ASSET_PATH . MEMBERS_ASSET_PATH, false, ARTICLE_MAX_IMAGE_WIDTH, ARTICLE_THUMBNAIL_WIDTH);
    $uploaded_files = $uploader->checkFileSizes()->uploadFiles()->getResponse();
    $uploaded_file = $uploaded_files[0];
    
    if ($uploaded_file['success'] || (!$uploaded_file['success'] && $uploaded_file['message'] == $uploaded_file['filename'] . " already exists")) {
        try {
            $query = "INSERT INTO
                Scheduled_Content (content_pub_date, content_type, content_heading, content_body, content_filename)
            VALUES (?, ?, ?, ?, ?);";
            $params = [
                $_POST['content_pub_date'],
                $_POST['content_type'],
                $_POST['content_heading'],
                $_POST['content_body'],
                $uploaded_file['filename']
            ];
            $mem_db->query($query, $params);
        } catch (Exception $e) {
            error_log($e->getMessage());
            die("<span class='error'>There has been an error - let the webmaster know</span>");
        }
    }
} else {
    try {
        $query = "INSERT INTO
            Scheduled_Content (content_pub_date, content_type, content_heading, content_body)
        VALUES (?, ?, ?, ?);";
        $params = [
            $_POST['content_pub_date'],
            $_POST['content_type'],
            $_POST['content_heading'],
            $_POST['content_body']
        ];
        $mem_db->query($query, $params);
    } catch (Exception $e) {
        error_log($e->getMessage());
        die("<span class='error'>There has been an error - let the webmaster know</span>");
    }
}
