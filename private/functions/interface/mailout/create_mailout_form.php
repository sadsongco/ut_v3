<?php

include("./includes/mailout_includes.php");

$filename = date("ymd");

echo $m->render("createMailout", ["filename"=>$filename]);