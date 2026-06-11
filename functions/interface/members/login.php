<?php

include_once(__DIR__ . '/../../../functions/functions.php');
global $auth;

include_once(base_path("../secure/env/ut.members.config.php"));
include_once(base_path("../secure/env/ut_reserved_usernames.php"));
include_once(base_path("classes/Database.php"));

try {

    if (filter_var($_POST['username'], FILTER_VALIDATE_EMAIL)) {
        $auth->login($_POST['username'], $_POST['password']);
    } else {
        $auth->loginWithUsername($_POST['username'], $_POST['password']);
    }
    header ('HX-Trigger:loginStatusChanged');
    echo 'User is logged in';
}
catch (\Delight\Auth\UnknownUsernameException $e) {
    die('Unknown username');
}
catch (\Delight\Auth\AmbiguousUsernameException $e) {
    die('Ambiguous username');
}
catch (\Delight\Auth\InvalidPasswordException $e) {
    die('Wrong password');
}
catch (\Delight\Auth\EmailNotVerifiedException $e) {
    die('Email not verified');
}
catch (\Delight\Auth\TooManyRequestsException $e) {
    die('Too many requests');
}

