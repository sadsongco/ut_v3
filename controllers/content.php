<?php

include(base_path('classes/ArticleRenderer.php'));

use Database\Database;
$db = new Database('content');

function getLatestArticleId ($db) {
    try {
        $query = "SELECT MAX(article_id) as latest_article_id FROM articles;";
        $result =  $db->query($query)->fetch();
        return $result['latest_article_id'];
    }
    catch (PDOException $e) {
        throw new Exception($e);
    }
}

function getArticle($db, $article_id) {
    try {
        $query = "SELECT title, body, pub_date, DATE_FORMAT(added, '%d %M %Y') as added FROM articles WHERE article_id = ?;";
        return $db->query($query, [$article_id])->fetch();
    }
    catch (PDOException $e) {
        throw new Exception($e->getMessage());
    }
}

function getPrevArticle($db, $article_id) {
    try {
        $query = "SELECT article_id FROM articles WHERE article_id < ? ORDER BY added DESC LIMIT 1;";
        $result = $db->query($query, [$article_id])->fetch();
        return $result['article_id'] ?? false;
    }
    catch (PDOException $e) {
        throw new Exception($e->getMessage());
    }
}

function getNextArticle($db, $article_id) {
    try {
        $query = "SELECT article_id FROM articles WHERE article_id > ? ORDER BY added ASC LIMIT 1;";
        $result = $db->query($query, [$article_id])->fetch();
        return $result['article_id'] ?? false;
    }
    catch (PDOException $e) {
        throw new Exception($e->getMessage());
    }
}

$host = getHost();

if (!$paths) {
    $show_tab = 1;
} else {
    $show_tab = $paths[0];
}

$show_article = getLatestArticleId($db);
if (isset($_GET['article_id']) && is_numeric($_GET['article_id'])) {
    $show_article = $_GET['article_id'];
}

$rendered_article = "";
try {
    $article = getArticle($db, $show_article);
    $prev_article = getPrevArticle($db, $show_article);
    $next_article = getNextArticle($db, $show_article);
    $renderer = new ArticleRenderer($article, "blog");
    $rendered_article = $renderer->renderArticle();
}
catch (Exception $e) {
    die ("System error: ".$e->getMessage());
}

$blog_stylesheets = ['articles'];

echo $this->renderer->render('blog', ['article'=>$rendered_article, 'article_id'=>$show_article,'next_article'=>$next_article, 'prev_article'=>$prev_article]);
