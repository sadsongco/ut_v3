<?php

include_once(__DIR__."/mailout_create.php");

function generateMailoutContent($mailout_data, $m, $db) {
    $text_content = createTextBody($mailout_data['body']);
    $html_content = createHTMLBody($mailout_data['body'], $m, $db);
    return [
        "subject"=>$mailout_data['subject'],
        "heading"=>$mailout_data['heading'],
        "text_content"=>$text_content,
        "html_content"=>$html_content,
        "host"=>"",
        "remove_path"=>"",
        "name"=>"",
        "email"=>"",
        "secure_id"=>"",
        "download_link"=>""
    ];
}