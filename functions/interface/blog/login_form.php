<?php

include(__DIR__ . "/../../functions.php");
include("includes/content_includes.php");

$host = getHost();
$target = "userModal";
if (isset($_GET['target'])) $target = $_GET['target'];
echo $m->render('login_form', ["base_dir"=>$host, "target"=>$target, "logged_in"=>$auth->isLoggedIn()]);
