<?php

include_once(__DIR__ . '/../../../functions/functions.php');
global $auth;

include_once(base_path("/../secure/env/ut.members.config.php"));
include_once(base_path("/../secure/env/ut_reserved_usernames.php"));
include_once(base_path("/classes/Database.php"));

try {
    $userId = $auth->registerWithUniqueUsername($_POST['email'], $_POST['password'], $_POST['username'], function ($selector, $token) {
        echo 'Send ' . $selector . ' and ' . $token . ' to the user (e.g. via email)';
        echo '  For emails, consider using the mail(...) function, Symfony Mailer, Swiftmailer, PHPMailer, etc.';
        echo '  For SMS, consider using a third-party service and a compatible SDK';
    });

    echo 'We have signed up a new user with the ID ' . $userId;
}
catch (\Delight\Auth\InvalidEmailException $e) {
    die('Invalid email address');
}
catch (\Delight\Auth\InvalidPasswordException $e) {
    die('Invalid password');
}
catch (\Delight\Auth\UserAlreadyExistsException $e) {
    die('User with this email already exists');
}
catch (\Delight\Auth\DuplicateUsernameException $e) {
    die('Username already exists');
}
catch (\Delight\Auth\TooManyRequestsException $e) {
    die('Too many requests');
}