<?php


// database
require_once("../../secure/scripts/ut_a_connect.php");

// utitlities
include(__DIR__."/../functions/functions.php");

global $auth;
$host = getHost();

// new password submitted
if (isset($_POST["reset_password"])) {
    if ($_POST['password'] != $_POST['password_conf'])
        die($m->render("members/reset_pw_result", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>"Passwords don't match"]));
    try {
        $auth->resetPassword($_POST['selector'], $_POST['token'], $_POST['password']);
        exit($m->render("members/reset_pw_result", ["base_dir"=>$host, "passwordReset"=>true, "message"=>'Password has been reset']));
    
    }
    catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
        error_log($e);
        die($m->render("members/reset_pw_result", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>'Invalid token']));
    }
    catch (\Delight\Auth\TokenExpiredException $e) {
        error_log($e);
        die($m->render("members/reset_pw_result", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>'Token expired']));
    }
    catch (\Delight\Auth\ResetDisabledException $e) {
        error_log($e);
        die($m->render("members/reset_pw_result", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>'Password reset is disabled']));
    }
    catch (\Delight\Auth\InvalidPasswordException $e) {
        error_log($e);
        die($m->render("members/reset_pw_result", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>'Invalid password']));
    }
    catch (\Delight\Auth\TooManyRequestsException $e) {
        error_log($e);
        die($m->render("members/reset_pw_result", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>'Too many requests']));
    }
    exit();
}

try {
    $auth->canResetPasswordOrThrow($_GET['selector'], $_GET['token']);
    
    echo $m->render("members/reset_pw_result", ["base_dir"=>$host, "reset_pw_resultForm"=> true, "selector"=>$_GET['selector'], "token"=>$_GET['token']]);
}
catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
    error_log($e);
    die($m->render("members/reset_pw_result", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>'Invalid token']));
}
catch (\Delight\Auth\TokenExpiredException $e) {
    error_log($e);
    die($m->render("members/reset_pw_result", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>'Token expired']));
}
catch (\Delight\Auth\ResetDisabledException $e) {
    error_log($e);
    die($m->render("members/reset_pw_result", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>'Password reset is disabled']));
}
catch (\Delight\Auth\TooManyRequestsException $e) {
    error_log($e);
    die($m->render("members/reset_pw_result", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>'Too many requests']));
}
catch (Exception $e) {
    error_log($e);
    die($m->render("members/reset_pw_result", ["base_dir"=>$host, "passwordResetError"=>true, "message"=>"There has been a background error"]));
}
