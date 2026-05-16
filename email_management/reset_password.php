<?php


// database
require_once("../../secure/scripts/ut_a_connect.php");

// utitlities
include(__DIR__."/../php/includes/baseDir.php");

// templating system
require '../lib/mustache.php-main/src/Mustache/Autoloader.php';
Mustache_Autoloader::register();

$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/../php/templates'),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/../php/templates/partials')
));

// auth
require __DIR__ . '/../php/vendor/autoload.php';
try {
    $auth = new \Delight\Auth\Auth($db);
}
catch (Exception $e) {
    error_log($e);
    exit ("There has been a background error");
}

$host = getHost();

// new password submitted
if (isset($_POST["reset_password"])) {
    if ($_POST['password'] != $_POST['password_conf'])
        die($m->render("resetPW", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>"Passwords don't match"]));
    try {
        $auth->resetPassword($_POST['selector'], $_POST['token'], $_POST['password']);
        exit($m->render("resetPW", ["base_dir"=>$host, "passwordReset"=>true, "message"=>'Password has been reset']));
    
    }
    catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
        die($m->render("resetPW", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>'Invalid token']));
    }
    catch (\Delight\Auth\TokenExpiredException $e) {
        die($m->render("resetPW", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>'Token expired']));
    }
    catch (\Delight\Auth\ResetDisabledException $e) {
        die($m->render("resetPW", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>'Password reset is disabled']));
    }
    catch (\Delight\Auth\InvalidPasswordException $e) {
        die($m->render("resetPW", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>'Invalid password']));
    }
    catch (\Delight\Auth\TooManyRequestsException $e) {
        die($m->render("resetPW", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>'Too many requests']));
    }
    exit();
}

try {
    $auth->canResetPasswordOrThrow($_GET['selector'], $_GET['token']);
    
    echo $m->render("resetPW", ["base_dir"=>$host, "resetPWForm"=> true, "selector"=>$_GET['selector'], "token"=>$_GET['token']]);
}
catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
    die($m->render("resetPW", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>'Invalid token']));
}
catch (\Delight\Auth\TokenExpiredException $e) {
    die($m->render("resetPW", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>'Token expired']));
}
catch (\Delight\Auth\ResetDisabledException $e) {
    die($m->render("resetPW", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>'Password reset is disabled']));
}
catch (\Delight\Auth\TooManyRequestsException $e) {
    die($m->render("resetPW", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>'Too many requests']));
}
catch (Exception $e) {
    die($m->render("resetPW", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>"There has been a background error"]));
}

require_once("../../secure/scripts/ut_disconnect.php");

?>