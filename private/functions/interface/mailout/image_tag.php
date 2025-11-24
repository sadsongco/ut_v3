<?php

require_once('includes/mailout_includes.php');
// require(base_path("../secure/env/config.php"));
require_once('includes/mailout_create.php');

try {
    $query = "SELECT * FROM MailoutImages WHERE img_id = ?";
    $result = $db->query($query, [$_GET['img']])->fetchAll();
}
catch (PDOException $e) {
    exit("Problem retrieving mailout images: ".$e->getMessage());
}

$result[0]['tag'] = "<!--{{i::".$result[0]['img_id']."}}-->";
$result[0]['path'] = getHost()."/serve/".MAILOUT_IMAGE_PATH . str_replace(".", "/", $result[0]['url']);

echo $m->render('existingImage', $result[0]);