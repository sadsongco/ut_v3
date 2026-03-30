<?php

/* ***
CRON command
cd /home/thesadso/unbelievabletruth.co.uk/private/functions/cron/mailout/; /usr/local/bin/php -q mailout.php
*** */

include(__DIR__ . "/../../../../functions/functions.php");
require(base_path("classes/Database.php"));
require_once(base_path('private/functions/interface/mailout/includes/mailout_includes.php'));

use Database\Database;
$db = new Database('admin');

// set other mailout variables
$subject_id = "[UNBELIEVABLE TRUTH]";
$log_dir =  $test ? base_path(WEB_ASSET_PATH . MAILOUT_LOG_PATH . 'test/') : base_path(WEB_ASSET_PATH . MAILOUT_LOG_PATH .'artprint/');

// email variables
$from_name = "Unbelievable Truth webshop";

/* *** INCLUDES *** */

require_once(base_path('private/functions/interface/mailout/includes/do_mailout.php?artprint=true'));