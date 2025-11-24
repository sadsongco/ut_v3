<?php

include(__DIR__ . "/../../functions.php");
require("includes/content_includes.php");

try {
    $auth->logOut();
}
catch (\Delight\Auth\NotLoggedInException $e) {
    die ("Not logged in");
}
catch (Exception $e) {
    echo $e->getMessage();
}

$protocol = 'http';
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') $protocol .= 's';
$host = "$protocol://".$_SERVER['HTTP_HOST'];
header ("HX-Redirect:$host");
die();