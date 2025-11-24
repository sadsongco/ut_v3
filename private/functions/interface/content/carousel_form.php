<?php

include(__DIR__ . "/../../../../functions/functions.php");
require(base_path("classes/Database.php"));
require(base_path("../lib/mustache.php-main/src/Mustache/Autoloader.php"));
Mustache_Autoloader::register();

$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/content/')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/content/partials/'))
));

use Database\Database;

$db = new Database('admin');

echo $m->render('carouselForm');