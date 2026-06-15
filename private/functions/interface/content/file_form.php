<?php

include (__DIR__ . "/../../../../functions/functions.php");
$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/partials'))
));

echo $m->render("content/uploadArticleFileForm", ["session_upload_name"=>ini_get("session.upload_progress.name")]);
