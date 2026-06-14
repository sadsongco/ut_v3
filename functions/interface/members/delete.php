<?php

include_once(__DIR__ . '/../../../functions/functions.php');

if (!$auth->isLoggedIn()) {
    header('HX-Trigger:loginStatusChanged');
    exit();
} else {
    $auth->logOut();
}

try {
    if (filter_var($_POST['username'], FILTER_VALIDATE_EMAIL)) {
        $auth->admin()->deleteUserByEmail($_POST['username']);
    } else {
        $auth->admin()->deleteUserByUsername($_POST['username']);
    }
    header ('HX-Trigger:loginStatusChanged');
    echo 'Your account has been deleted';
}


catch (\Delight\Auth\InvalidEmailException $e) {
    die('Unknown email address');
}
catch (\Delight\Auth\UnknownUsernameException $e) {
    die('Unknown username');
}
catch (\Delight\Auth\AmbiguousUsernameException $e) {
    die('Ambiguous username');
}