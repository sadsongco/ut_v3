<?php

include(__DIR__ . "/../../../../functions/functions.php");

$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/content/')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/content/partials/'))
));

$id = $_GET["fileId"];

echo $m->render("file_upload", [
    "id"=>$id,
    "max_size"=>MAX_FILE_SIZE,
    "accept"=>"image/jpeg, , image/gif, audio/mp3"
]);

?>