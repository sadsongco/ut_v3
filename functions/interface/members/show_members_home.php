<?php

include_once(__DIR__ . '/../../../functions/functions.php');
global $auth;

$env = false;
if (ENV === 'dev') $env = 'dev';

echo $m->render('members/home_page', [
    "env"=>$env,
    "stylesheets"=>["members"],
    "logged_in"=>$auth->isLoggedIn(),
    "nav"=>true
]);