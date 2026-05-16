<?php

// database
require_once("../../secure/scripts/ut_a_connect.php");

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
    exit("There has been a background error");
}

function addEmailToList($email) {
    require_once("../../secure/scripts/ut_m_connect.php");
    echo "Add Email $email To List";

    try {
        $stmt = $db->prepare("INSERT INTO ut_mailing_list (email, name, domain, subscribed, confirmed, date_added) VALUES (?, ?, SUBSTRING_INDEX(?, '@', -1), ?, ?, NOW());");
        $stmt->execute([$email, '', $email, 1, 1]);
        $_GET['check'] = hash('ripemd128', $email.$db->lastInsertId().'AndyJasNigel');
    }
    catch(PDOException $e) {
        if ($e->getCode() == 23000) return;
        error_log($e);
    }

    require_once("../../secure/scripts/ut_disconnect.php");
}

try {
    $auth->confirmEmailAndSignIn($_GET['selector'], $_GET['token']);
    addEmailToList($auth->getEmail());
    echo $m->render('emailConfirmed');
}
catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
    die ($m->render('emailConfirmed', ["error"=>true, "message"=>"Invalid token - make sure you're coming to this site from the link in your email"]));
}
catch (\Delight\Auth\TokenExpiredException $e) {
    die ($m->render('emailConfirmed', ["error"=>true, "message"=>"Token expired - make sure you're coming to this site from the link in your email"]));
}
catch (\Delight\Auth\UserAlreadyExistsException $e) {
    die ($m->render('emailConfirmed', ["error"=>true, "message"=>"Email address already exists"]));
}
catch (\Delight\Auth\TooManyRequestsException $e) {
    die ($m->render('emailConfirmed', ["error"=>true, "message"=>"Too many requests"]));
}

require_once("../../secure/scripts/ut_disconnect.php");

?>