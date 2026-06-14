<?php

include(__DIR__ . "/../../functions.php");
include("includes/content_includes.php");

$host = getHost();
$target = "user-management";

if (!$auth->isLoggedIn()) exit($m->render('members/login_form', ["base_dir"=>$host, "target"=>$target, "logged_in"=>$auth->isLoggedIn()]));

echo $m->render('members/manage', ["base_dir"=>$host, "target"=>$target, "logged_in"=>$auth->isLoggedIn()]);