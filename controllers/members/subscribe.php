<?php

use Database\Database;
$conn = new Database('members');
global $auth;

$env = false;
if (ENV === 'dev') $env = 'dev';

echo $this->renderer->render('members/subscribe', [
    "env"=>$env,
    "stylesheets"=>["members", "shop"],
    "logged_in"=>$auth->isLoggedIn(),
    "user_id"=>$auth->getUserId(),
    "nav"=>true
]);
