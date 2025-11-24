<?php

include('../functions/functions.php');

// load common classes
require base_path('classes/ShopRouter.php');
require base_path('classes/Database.php');

if (!isset($m)) {
    // load mustache for all controllers
    require base_path('../lib/mustache.php-main/src/Mustache/Autoloader.php');
    Mustache_Autoloader::register();
    $m = new Mustache_Engine(array(
        'loader' => new Mustache_Loader_FilesystemLoader(base_path('views')),
        'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('views/partials'))
    ));
}

use Router\ShopRouter;
$router = new ShopRouter($m, true);
