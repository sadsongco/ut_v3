<?php

include_once(__DIR__ . "/mailout_includes.php");
include_once(base_path("../secure/secure_id/secure_id_ut.php"));

function generateMailoutEmailContent($replacements, $data, $m) {
    $secure_id = generateSecureId($data['email'], $data['email_id']);
    $replacements['name'] = $data['name'];
    $replacements['email'] = $data['email'];
    $replacements['secure_id'] = $secure_id;
    $text_body = $m->render("textTemplate", $replacements);
    $html_body = $m->render("htmlTemplate", $replacements);

    return [
        "text_body"=>$text_body,
        "html_body"=>$html_body,
        "subject"=>$replacements["subject"]
    ];        

}