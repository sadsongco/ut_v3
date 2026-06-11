<?php

use Database\Database;
$content_db = new Database('content');

require(base_path("../secure/scripts/ut_u_connect.php"));

// templating
$m_emails = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path("views/members")),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path("views/partials/members"))
));

// auth
require_once(base_path('../lib/vendor/autoload.php'));
try {
    $auth = new \Delight\Auth\Auth($auth_db);
}
catch (Exception $e) {
    die($e->getMessage());
}

