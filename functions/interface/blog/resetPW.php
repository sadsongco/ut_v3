<?php

include(__DIR__ . "/../../functions.php");
include("includes/content_includes.php");

$target = "userModal";
if (isset($_GET['target'])) $target = $_GET['target'];


echo $m->render("request_pw_reset", ["base_dir"=>getHost(), "target"=>$target]);

?>