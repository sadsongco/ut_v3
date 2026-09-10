<?php

function renderArticle($content) {
    $m_members = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('views/members')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('views/partials/members'))
    ));
    $date = new DateTime($content["content_pub_date"]);
    $content['date'] = $date->format("jS M Y - g:iA");
    $content['content_body'] = nl2br($content['content_body']);

    return $m_members->render("article", [
        "content" => $content
    ]);
}
