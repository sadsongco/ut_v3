<?php
function replaceTags($body_template, $row) {
    $secure_id = generateSecureId($row['email'], $row['email_id']);
    $row['secure_id'] = $secure_id;
    $row['host'] = getHost();
    foreach ($row as $tag_name=>$tag_content) {
        if ($tag_name == 'name' && $tag_content == '') $tag_content = 'Music Friend';
        $body_template = str_replace("{{".$tag_name."}}", $tag_content, $body_template);
    }
    return $body_template;
}