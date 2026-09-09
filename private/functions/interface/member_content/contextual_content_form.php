<?php
include(__DIR__ . "/../../../../functions/functions.php");
include(__DIR__ . "/includes/member_content_includes.php");

$text_fields = $_POST['content_type'] == "text" || $_POST['content_type'] == "audio" || $_POST['content_type'] == "video";
$file_field = $_POST['content_type'] == "audio" || $_POST['content_type'] == "video";
$accept = $_POST['content_type'] . "/*";
$content_heading = isset($_POST['content_heading']) ? $_POST['content_heading'] : "";
$content_body = isset($_POST['content_body']) ? $_POST['content_body'] : "";

$timezone = new DateTimeZone("Europe/London");
$date = isset($_POST['content_pub_date']) ? new DateTime($_POST['content_pub_date'], $timezone) : new DateTime('now', $timezone);

echo $m->render("contextualContentForm", [
    "content_type" => $_POST['content_type'],
    "content_heading" => $content_heading,
    "content_body" => $content_body,
    "text_fields" => $text_fields,
    "file_field" => $file_field,
    "accept" => $accept,
    "date" => $date->format("Y-m-d\TH:i")
]);