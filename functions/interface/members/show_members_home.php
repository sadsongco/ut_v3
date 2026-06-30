<?php

require_once(__DIR__ . '/../../../functions/functions.php');
require_once(base_path("classes/Database.php"));
global $auth;

$env = false;
if (ENV === 'dev') $env = 'dev';

$subscribed = $premium = false;
use Database\Database;
$mem_db = new Database('members');
$query = "SELECT status FROM Subscriptions WHERE user_id = ?";
try {
    $result = $mem_db->query($query, [$auth->getUserId()])->fetch();
} catch (Exception $e) {
    error_log($e);
    exit("There has been an error. Please contact the webmaster.");
}

if($result && $result['status']) {
    $subscribed = true;
}
if ($result && $result['status'] == "PREMIUM") {
    $premium = true;
}

echo $m->render('members/home_page', [
    "env"=>$env,
    "stylesheets"=>["members"],
    "logged_in"=>$auth->isLoggedIn(),
    "subscribed"=>$subscribed,
    "premium"=>$premium,
    "nav"=>true
]);