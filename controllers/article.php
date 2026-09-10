<?php

require_once(__DIR__ . "/../functions/functions.php");
include(base_path('classes/ArticleRenderer.php'));

use Database\Database;
$db = new Database('content');

try {
    $query = "SELECT
        article_id,
        title,
        body,
        added,
        tab,
        posted_by,
        tab_name
        FROM articles
        LEFT JOIN tabs ON tab = tabs.tab_id
        WHERE article_id = ?;";
    $result = $db->query($query, [1])->fetchAll();
}
catch (Exception $e) {
    die("DATABASE ERROR: ".$e->getMessage());
}

$article = $result[0];
use ArticleRenderer\ArticleRenderer;
$renderer = new ArticleRenderer($article, "members");
echo $renderer->renderArticle();