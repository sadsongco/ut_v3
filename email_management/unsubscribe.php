<?php

include_once("./includes/html_head.php");

require(__DIR__ . "/../functions/functions.php");
require_once(base_path("classes/Database.php"));
use Database\Database;
$db = new Database('mailing_list');

$message = "<p>Unbelievable Truth email unsubscribe page. You can access this through the link provided in your email";

if (isset($_GET['email']) && $_GET['email'] != '') {
    try {
        $query = "SELECT email_id FROM ut_mailing_list WHERE email=?;";
        $result = $db->query($query, [$_GET['email']])->fetch();
        $db_id = 0;
        if (!isset($result) || empty($result)) exit("Email not found in database");
        $db_id = $result['email_id'];
        $secure_id = hash('ripemd128', $_GET['email'].$db_id.'AndyJasNigel');
        if ($secure_id != $_GET['check']) {
            throw new PDOException('Bad Check Code', 1176);
        }
        $query = "DELETE FROM ut_mailing_list WHERE email_id=? and email=?";
        $db->query($query, [$db_id, $_GET['email']]);
        $message = "<h2>Your email has been removed from the Unbelievable Truth mailing list.</h2>";
    }
    catch(PDOException $e) {
        if ($e->getCode() != 1176) {
            error_log($e);
            $message = "<p>Sorry, there was a background error</p>";
        }
        else {
            $message =  '<h2>'.$e->getMessage().' - please make sure you have accessed this page through the unsubscribe link provided in your email</h2>';
        }
    }
}

echo $message;

include_once("./includes/html_foot.php");
