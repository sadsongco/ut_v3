<?php

include(__DIR__ . "/../../functions.php");
require("includes/content_includes.php");

define("REMEMBER_DURATION", (int) 60 * 60 * 24 * 30);

try {
    $rememberDuration = null;
    if (isset($_POST["remember"]) && $_POST['remember'] == "on") {
        $rememberDuration = REMEMBER_DURATION;
    } 
    $auth->login($_POST['email'], $_POST['password'], $rememberDuration);
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') $protocol .= 's';    $host = "$protocol://".$_SERVER['HTTP_HOST'];
    echo $m->render('login_form', ["username"=>$auth->getUsername(), "base_dir"=>$host, "logged_in"=>$auth->isLoggedIn()]);
}
catch (\Delight\Auth\InvalidEmailException $e) {
    die('Wrong email address');
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

?>