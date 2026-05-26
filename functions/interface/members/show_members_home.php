<?php

include_once(__DIR__ . '/../../../functions/functions.php');
global $auth;
// load mustache for all controllers
require base_path('../lib/mustache.php-main/src/Mustache/Autoloader.php');
Mustache_Autoloader::register();
$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('views')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('views/partials'))
));
$env = false;
if (ENV === 'dev') $env = 'dev';

echo $m->render('members/home_page', [
    "env"=>$env,
    "stylesheets"=>["members"],
    "logged_in"=>$auth->isLoggedIn(),
    "nav"=>true
]);