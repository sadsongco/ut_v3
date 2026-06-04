<?php

use Database\Database;
$db = new Database('admin');
require_once(base_path("functions/email/send_confirmation_email.php"));
require_once(base_path("functions/email/add_email_to_db.php"));

include_once(base_path('../secure/secure_id/secure_id_ut.php'));
include_once(base_path('private/functions/interface/mailout/includes/replace_tags.php'));

