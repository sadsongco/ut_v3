<?php

include('../functions/functions.php');
require('classes/AdminRouter.php');

// load mustache for all controllers

$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/partials/'))
));

use Router\AdminRouter;

$router = new AdminRouter($m);