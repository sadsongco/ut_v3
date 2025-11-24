<?php

include('../functions/functions.php');
require('classes/AdminRouter.php');

require base_path('classes/Database.php');

// load mustache for all controllers
require base_path('../lib/mustache.php-main/src/Mustache/Autoloader.php');
Mustache_Autoloader::register();
$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/partials/'))
));

use Router\AdminRouter;

$router = new AdminRouter($m);