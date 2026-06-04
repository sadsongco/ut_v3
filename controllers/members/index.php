<?php

use Database\Database;
$conn = new Database('members');
global $auth;

$env = false;
if (ENV === 'dev') $env = 'dev';

echo $this->renderer->render('members/index', [
    "env"=>$env,
    "stylesheets"=>["members"],
    "logged_in"=>$auth->isLoggedIn(),
    "nav"=>true
]);

// if ($auth->isLoggedIn()) {
//     $query = "SELECT * FROM Subscriptions WHERE user_id = ?";
//     $result = $conn->query($query, [$auth->getUserId()])->fetchAll();
// }
// else {
//     echo $this->renderer->render('members/login');
// }

