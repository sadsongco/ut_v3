<?php

include_once("includes/mailout_includes.php");
// include_once(base_path("../secure/env/config.php"));

$test = isset($_POST['test_mailout']); 

$body = $_POST['mailout'];
if ($test) $body = "test:" .  $body;

try {
    $fp = fopen(base_path(WEB_ASSET_PATH . CURRENT_MAILOUT_PATH), 'w');
    fwrite($fp, $body);
    fclose($fp);
}
catch (Exception $e) {
    echo "ERROR";
    exit();
}

echo "Mailout <span class='underline'>".$_POST['mailout']."</span> set to send";
if ($test) echo " to test mailing list";