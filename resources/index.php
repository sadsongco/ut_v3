<?php
include('../functions/functions.php');
// load common classes
require base_path('classes/ResourcesRouter.php');
require base_path('classes/Database.php');

use Router\ResourcesRouter;
$router = new ResourcesRouter($m, true);
