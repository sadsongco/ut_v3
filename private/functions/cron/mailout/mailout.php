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

$current_mailout_file = base_path(WEB_ASSET_PATH . CURRENT_MAILOUT_PATH);
$current_mailout_contents = file_get_contents($current_mailout_file);
if ($current_mailout_contents == '') exit();
date_default_timezone_set('Europe/London');

// current mailout is set, carry on

// get mailout data
$test = false;
$mailout_arr = explode(":", $current_mailout_contents);
if ($mailout_arr[0] == "test") {
    $test = true;
    $current_mailout_id = $mailout_arr[1];
} else {
    $current_mailout_id = $mailout_arr[0];
}

$current_mailout = getCurrentMailout($db, $current_mailout_id);
// set other mailout variables
$remove_path = '/email_management/unsubscribe.php';
$subject_id = "[UNBELIEVABLE TRUTH]";
$mailing_list_table = $test ? "test_mailing_list" : "ut_mailing_list";
$log_dir =  $test ? base_path(WEB_ASSET_PATH . MAILOUT_LOG_PATH . 'test/') : base_path(WEB_ASSET_PATH . MAILOUT_LOG_PATH .'logs/');

// email variables
$from_name = "Unbelievable Truth mailing list";

/* *** INCLUDES *** */

require_once(base_path('private/functions/interface/mailout/includes/do_mailout.php'));