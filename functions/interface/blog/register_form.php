<?php

include(__DIR__ . "/../../functions.php");
include("includes/content_includes.php");

if ($auth->isLoggedIn()) {
    echo $m->render('userLoggedIn', ["username"=>$auth->getUsername()]);
} else {
    $host = getHost();
    $target = "userModal";
    if (isset($_GET['target'])) $target = $_GET['target'];    
    echo $m->render('user_register', ["base_dir"=>$host, "target"=>$target]);
}