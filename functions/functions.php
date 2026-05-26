<?php

require(__DIR__ . "/../../secure/scripts/ut_u_connect.php");


include_once(__DIR__ . '/includes/base_path.php');
include_once(__DIR__ . '/includes/dd.php');
include_once(__DIR__ . '/includes/get_host.php');
include_once(base_path("../secure/env/config.php"));
include_once(base_path("../secure/env/ut_reserved_usernames.php"));

// auth
require_once(__DIR__ . '/../../lib/vendor/autoload.php');
global $auth;

try {
    $auth = new \Delight\Auth\Auth($auth_db);
}
catch (Exception $e) {
    die($e->getMessage());
}