<?php

include(__DIR__ . "/../../../../functions/functions.php");
include_once("includes/resource_includes.php");
include_once(base_path("private/classes/FileUploader.php"));
use FileUploader\FileUploader;


if(!isset($_POST['resource_dir'])) {
    echo $m->render("partials/resourceForm", ["dir"=>$_POST["resource_dir"], "error"=>["message"=>"No resource directory specified. Could be that the file is too big to upload and has to be uploaded manually"]]);
    exit();
}

$uploaded_files = false;

if (!empty($_FILES)) {
    $uploader = new FileUploader(RESOURCE_ASSET_PATH . $_POST['resource_dir'], true);
    if ($_POST['resource_dir'] == "logos") {
        $uploader = new FileUploader(RESOURCE_ASSET_PATH . $_POST['resource_dir'], true, false, false);
    }
    $uploaded_files = $uploader->checkFileSizes()->getResponse();
    
    if (isset($uploaded_files["success"]) && !$uploaded_files["success"]) {
        echo $m->render("partials/resourceForm", ["dir"=>$_POST["resource_dir"], "error"=>$uploaded_files]);
        exit();
    }
    $uploaded_files = $uploader->uploadFiles()->getResponse();
    
    if (isset($uploaded_files[0]["success"]) && !$uploaded_files[0]["success"]) {
            echo $m->render("partials/resourceForm", ["dir"=>$_POST["resource_dir"], "error"=>$uploaded_files[0]]);
            exit();
    }
    $_POST['file'] = $uploaded_files[0]['filename'];
}



$fields = null;
if (isset($_POST["meta_filename"]) && $_POST["meta_filename"] != "") {
    $file_path = base_path(RESOURCE_ASSET_PATH) . $_POST["resource_dir"] . "/" . $_POST["meta_filename"] . ".txt";
    $fields = $update_map[$_POST["meta_filename"]];
    $res_str_arr = [];
    foreach ($fields as $field) {
        $res_str_arr[] = $_POST[$field];
    }
    $res_str = implode("|", $res_str_arr) . "\n";
    file_put_contents($file_path, $res_str, FILE_APPEND);
    echo "<h2>Resource meta file updated</h2>";
}

if (isset($fields) && $fields) {
    foreach ($fields as $key=>$field) {
        if ($field == 'file') unset($fields[$key]);
    }
} else {
    $fields = [];
}
$meta_file = $_POST['meta_filename'] ?? null;

echo $m->render("partials/resourceForm", ["dir"=>$_POST["resource_dir"], "meta_file"=>$meta_file, "fields"=>[...$fields], "uploaded_files"=>$uploaded_files]);
