<?php

include(__DIR__ . "/../../../../functions/functions.php");

$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/content/')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/content/partials/'))
));

use Database\Database;

$db = new Database('admin');

echo $m->render('carouselForm');