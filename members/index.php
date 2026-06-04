<?php

include(__DIR__ . '/../functions/functions.php');

// load common classes
require base_path('classes/MembersRouter.php');

use Router\MembersRouter;
$router = new MembersRouter($m, true);
