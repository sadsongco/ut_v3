<?php

include('functions/functions.php');

// load common classes
require base_path('classes/Router.php');
require base_path('classes/Database.php');

use Router\Router;

$router = new Router($m);
