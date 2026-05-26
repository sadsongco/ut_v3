<?php

include_once(__DIR__ . '/../../../functions/functions.php');
global $auth;

try {
    $auth->logOutEverywhere();
    header ('HX-Trigger:loginStatusChanged');
    echo 'User is logged out';
}
catch (\Delight\Auth\NotLoggedInException $e) {
    die('Not logged in');
}