<?php

error_reporting(E_ALL); // Error/Exception engine, always use E_ALL

ini_set('ignore_repeated_errors', TRUE); // always use TRUE

ini_set('display_errors', FALSE); // Error/Exception display, use FALSE only in production environment or real server. Use TRUE in development environment

ini_set('log_errors', TRUE); // Error/Exception file logging engine.
ini_set('error_log', './debug.log'); // Logging file path

require_once(base_path("classes/Database.php"));
use Database\Database;
$db = new Database('admin');

require_once(base_path("functions/email/send_confirmation_email.php"));
require_once(base_path("functions/email/add_email_to_db.php"));

include_once(base_path('../secure/secure_id/secure_id_ut.php'));
include_once(base_path('private/functions/interface/mailout/includes/replace_tags.php'));

