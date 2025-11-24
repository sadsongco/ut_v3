<?php

require_once(__DIR__ . "/../../functions.php");
require(__DIR__ . "/includes/content_includes.php");

$params = [
    "logged_in"=>$auth->isLoggedIn(),
    "username"=>$auth->getUsername(),
    "article_id"=>$_POST['article_id'],
    "tab_id"=>$_POST['tab_id'],
    "comment_reply_id"=>$_POST["comment_id"]
];

echo $m->render("comment_form_solo", $params);