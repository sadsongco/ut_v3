<?php

require(__DIR__ . "/../../../../functions/functions.php");
require(base_path("classes/Database.php"));
require(base_path("private/classes/FileUploader.php"));

use Database\Database;
$db = new Database('orders');

use FileUploader\FileUploader;
$uploader = new FileUploader(WEB_ASSET_PATH . SHOP_ASSET_PATH, false, SHOP_MAX_IMAGE_WIDTH, SHOP_THUMBNAIL_WIDTH);
$uploaded_files = $uploader->checkFileSizes()->uploadFiles()->getResponse();
$_POST['image'] = $uploaded_files[0]['filename'];

if(isset($_POST['featured'])) $_POST['featured'] = $_POST['featured'] == "on" ? 1 : NULL;
if(isset($_POST['e_delivery'])) $_POST['e_delivery'] = $_POST['e_delivery'] == "on" ? 1 : NULL;

if ($_POST['release_date'] == "") unset($_POST['release_date']);

$update = [];
$params = [];
foreach ($_POST as $field=>$value) {
    $params[$field] = $value;
    if ($field == 'item_id') continue;
    $update[] = $field;
}
$columns = "(" . implode(", ", $update) . ")";
$values = "(:" . implode(", :", $update) . ")";
$query = "INSERT INTO Items $columns VALUES $values";

$db->query($query, $params);

header("HX-Trigger: stockUpdated");
echo "<h1>New Item Added</h1>";