<?php

include('../functions/functions.php');

// load common classes
require base_path('classes/ShopRouter.php');

use Router\ShopRouter;
$router = new ShopRouter($m, true);
