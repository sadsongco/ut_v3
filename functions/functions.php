<?php

require(__DIR__ . "/../../secure/scripts/ut_u_connect.php");

include_once(__DIR__ . '/includes/base_path.php');
include_once(__DIR__ . '/includes/dd.php');
include_once(__DIR__ . '/includes/get_host.php');
include_once(base_path("../secure/env/config.php"));
include_once(base_path("../secure/env/ut_reserved_usernames.php"));
require_once(base_path("classes/Database.php"));

// auth
require_once(__DIR__ . '/../../lib/vendor/autoload.php');
global $auth;

try {
    $auth = new \Delight\Auth\Auth($auth_db);
}
catch (Exception $e) {
    die($e->getMessage());
}

// templating engine

require base_path('../lib/mustache.php-main/src/Mustache/Autoloader.php');
Mustache_Autoloader::register();
$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('views')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('views/partials'))
));