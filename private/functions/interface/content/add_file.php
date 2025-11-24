<?php

include(__DIR__ . "/../../../../functions/functions.php");
// templating
require base_path('../lib/mustache.php-main/src/Mustache/Autoloader.php');
Mustache_Autoloader::register();

$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/content/')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/content/partials/'))
));

$id = $_GET["fileId"];

echo $m->render("file_upload", ["id"=>$id, "max_size"=>MAX_FILE_SIZE]);

?>