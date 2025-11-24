<?php

require_once('includes/mailout_includes.php');

// paths to email data
$remove_path = '/email_management/unsubscribe.php';

if (!isset($_POST['mailout'])) exit("Select a mailout to preview...");

$mailout_data = getMailoutData($_POST['mailout'], $db);

echo $m->render("createMailout", ["id"=>$_POST['mailout'], "subject"=> $mailout_data['subject'], "heading"=>$mailout_data['heading'], "body"=>$mailout_data['body'], "edit"=>true]);
