<?php

function renderArticle($title, $body, $date) {
    $m_members = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('views/members')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('views/partials/members'))
    ));
    $date = new DateTime($date);
    
    return $m_members->render("article", [
        "title"=>$_POST["content_heading"],
        "body"=>nl2br($_POST["content_body"]),
        "date"=>$date->format("jS M Y - g:iA")
    ]);
}
