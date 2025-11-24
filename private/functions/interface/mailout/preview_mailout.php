<?php

require_once('includes/mailout_includes.php');
require_once('includes/mailout_create.php');

if (isset($_POST['preview_mailout'])) {
    $mailout_data = $_POST;
    $mailout_data['id'] = 999;
    $now = new DateTime;
    $mailout_date['date'] = $now->format('Y-m-d H:i:s');
} else {
    if (!isset($_GET['mailout'])) exit("Select a mailout to preview...");
    
    $mailout_data = getMailoutData($_GET['mailout'], $db);
}

if (!isset($mailout_data)) exit("Select a mailout to preview...");

$email = "previewemail@preview.com";
$id = 1;

require(base_path("../secure/secure_id/secure_id_ut.php"));
include_once(__DIR__."/includes/generate_mailout_content.php");
include_once(__DIR__."/includes/generate_mailout_email_content.php");
$remove_path = '/email_management/unsubscribe.php';

// $secure_id = generateSecureId($email, $id);
$replacements = generateMailoutContent($mailout_data, $m, $db);
$replacements['host'] = getHost();
$replacements['remove_path'] = $remove_path;

$data = ["name"=>"Preview Name", "email"=>$email, "email_id"=>$id];
$bodies = generateMailoutEmailContent($replacements, $data, $m);

echo $m->render('mailoutPreview', $bodies);