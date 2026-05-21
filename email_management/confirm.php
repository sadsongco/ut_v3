<?php

error_reporting(E_ALL); // Error/Exception engine, always use E_ALL

ini_set('ignore_repeated_errors', TRUE); // always use TRUE

ini_set('display_errors', FALSE); // Error/Exception display, use FALSE only in production environment or real server. Use TRUE in development environment

ini_set('log_errors', TRUE); // Error/Exception file logging engine.
ini_set('error_log', './debug.log'); // Logging file path

require_once(__DIR__ . "/../functions/functions.php");
require(base_path('classes/Database.php'));
require_once(base_path("../lib/mustache.php-main/src/Mustache/Autoloader.php"));
include_once("./includes/html_head.php");
include_once(base_path('../secure/secure_id/secure_id_ut.php'));

include_once(base_path('private/functions/interface/mailout/includes/replace_tags.php'));
include_once('./includes/get_email_id_from_db.php');
include_once('./includes/confirm_email_in_db.php');
include_once('./includes/send_last_mailout.php');
include_once('./includes/set_last_mailout_sent.php');

use Database\Database;
$db = new Database('mailing_list');

// Load Mustache
Mustache_Autoloader::register();

$m = new Mustache_Engine(array(
'loader' => new Mustache_Loader_FilesystemLoader(base_path('/private/views/mailout/')),
'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/mailout/partials/'))
));
$message = 'There was an error. Make sure you only access this page from your email link';

if (isset($_GET) && isset($_GET['email'])) {
    try {
        $email_id_result = getEmailIdFromDB($_GET['email'], $db);
        if (!isset($email_id_result['last_sent'])) $email_id_result['last_sent'] = 0;
        if (!$email_id_result["success"]) throw new Exception($email_id_result["status"]);
        $secure_id = generateSecureId($_GET['email'], $email_id_result['email_id']);
        if (!isset($_GET['check']) || $_GET['check'] != $secure_id) throw new Exception('Bad check code', 1176);
        $confirm_result = confirmEmailInDB($email_id_result['email_id'], $db);
        if (!$confirm_result["success"]) throw new Exception($confirm_result["status"]);
        $message = 'Your email is confirmed, welcome to the email list. The last mailout from the list should be in your inbox now. Thank you!';
    }
    catch (Exception $e) {
        if ($e->getCode() ==1176) {
            error_log('Bad check code');
        }
        error_log($e);
        exit($message);
    }
    $data = [...$_GET];
    $data['email_id'] = $email_id_result['email_id'];
    $last_mailout_result = sendLastMailout($data, $email_id_result['last_sent'], $db, $m);
    if ($last_mailout_result["success"]) {
        setLastMailoutSent($email_id_result['email_id'], $last_mailout_result["last_mailout"], $db);
    }
    else {
        $message .= "<br>There was an error sending the last mailout to you though. Contact info@unbelievabletruth.co.uk if you need help.";
    }
    
}

echo $message;

include_once("./includes/html_foot.php");
