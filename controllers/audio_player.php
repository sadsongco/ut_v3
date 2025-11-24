<?php

include (__DIR__ . "/../functions/functions.php");
// templating
require base_path('../lib/mustache.php-main/src/Mustache/Autoloader.php');
Mustache_Autoloader::register();

$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path("views")),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path("views/partials"))
));

$track = $_POST; //VALIDATE?
$track["host"] = getHost();
$track["title"] = str_replace("_", " ", $track["title"]);
$track["notes"] = str_replace("_", " ", nl2br($track["notes"]));
$track["path"] = "/serve/" . ARTICLE_ASSET_PATH . "audio/" . str_replace(".", "/", $track["filename"]);

echo $m->render("audio_track", $track);