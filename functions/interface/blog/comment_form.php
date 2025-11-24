<?php

include_once(__DIR__ . "/../../functions.php");
include("includes/content_includes.php");


echo $m->render("comment_form_solo", ["base_dir"=>getHost(), "logged_in"=>$auth->isLoggedIn(), "article_id"=>$_GET['article_id'], "username"=>$auth->getUsername()]);