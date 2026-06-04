<?php

$env = false;
if (ENV === 'dev') $env = 'dev';

include(base_path("private/functions/utility/nl2p.php"));

use Database\Database;
$db = new Database();


if (!isset($_GET['article_id'])) {
    $article_id = $db->query("SELECT MAX(article_id) as latest_article_id FROM articles;")->fetch()['latest_article_id'];
} else {
    $article_id = $_GET['article_id'];
}

echo $this->renderer->render('blog_page', [
    'nav'=>$this->nav,
    'article_id'=>$article_id,
    'stylesheets'=>['articles', 'comments'],
    'env'=>$env,
    'v'=>$v
]);
