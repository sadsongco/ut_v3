<?php

require_once('includes/mailout_includes.php');
require_once('includes/mailout_create.php');

try {
    $query = "SELECT * FROM MailoutImages";
    $result = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    $image_options = [];
    foreach ($result AS $image) {
        $image_options[] = ["filename"=>$image['url'], "id"=>$image['img_id']];
    }
}
catch (PDOException $e) {
    exit("Error retrieving existing images: ".$e->getMessage());
}

echo $m->render('selectImage', ["image_options"=>$image_options]);