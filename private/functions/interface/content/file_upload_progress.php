<?php

include(__DIR__ . "/../../../../functions/functions.php");
// templating
require base_path('../lib/mustache.php-main/src/Mustache/Autoloader.php');
Mustache_Autoloader::register();

$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/content/')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/content/partials/'))
));
session_start();

$key = ini_get("session.upload_progress.prefix") . "123";
if (!empty($_SESSION[$key])) {
    $current = $_SESSION[$key]["bytes_processed"];
    $total = $_SESSION[$key]["content_length"];
    $progress = $current < $total ? ceil($current / $total * 100) : 100;
    echo $m->render("fileUploadProgress", ["uploadProgress"=>$progress]);
}
else {
}

// if (isset($_SESSION)) echo "UPLOADING FILE...";

?>