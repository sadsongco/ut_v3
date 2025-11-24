<?php

include("../../functions.php");
require(base_path("classes/Database.php"));

use Database\Database;
$db = new Database('orders');

// load mustache template engine
require base_path('../lib/mustache.php-main/src/Mustache/Autoloader.php');
Mustache_Autoloader::register();
$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('views')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('views/partials'))
));

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'failed') {
        echo $m->render('shop/checkout_failed');
        exit();
    }
    if ($_GET['status'] == 'timeout') {
        echo $m->render('shop/checkout_timeout');
        exit();
    }
}