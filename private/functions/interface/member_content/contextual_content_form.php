<?php
include(__DIR__ . "/../../../../functions/functions.php");
include(__DIR__ . "/includes/member_content_includes.php");

$text_fields = $_POST['content_type'] == "text" || $_POST['content_type'] == "audio" || $_POST['content_type'] == "video";
$file_field = $_POST['content_type'] == "audio" || $_POST['content_type'] == "video";
$accept = $_POST['content_type'] . "/*";
$title = isset($_POST['title']) ? $_POST['title'] : "";
$body = isset($_POST['body']) ? $_POST['body'] : "";

$timezone = new DateTimeZone("Europe/London");
$date = isset($_POST['pub_date']) ? new DateTime($_POST['pub_date'], $timezone) : new DateTime('now', $timezone);

echo $m->render("contextualContentForm", [
    "content_type" => $_POST['content_type'],
    "title" => $title,
    "body" => $body,
    "text_fields" => $text_fields,
    "file_field" => $file_field,
    "accept" => $accept,
    "date" => $date->format("Y-m-d\TH:i")
]);