<?php

include_once(base_path("classes/Database.php"));
use Database\Database;
$content_db = new Database('content');

require(base_path("../secure/scripts/ut_c_connect.php"));

// templating
include_once(base_path("../lib/mustache.php-main/src/Mustache/Autoloader.php"));
Mustache_Autoloader::register();

$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path("views/blog")),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path("views/blog/partials"))
));

// auth
require_once(base_path('../lib/vendor/autoload.php'));
try {
    $auth = new \Delight\Auth\Auth($db);
}
catch (Exception $e) {
    die($e->getMessage());
}

