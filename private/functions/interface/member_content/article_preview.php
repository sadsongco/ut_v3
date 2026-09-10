<?php

include(__DIR__ . "/../../../../functions/functions.php");
include(base_path('classes/ArticleRenderer.php'));

use Database\Database;
$db = new Database('members');

$article = $_POST;

use ArticleRenderer\ArticleRenderer;
$renderer = new ArticleRenderer($article, "members");
echo $renderer->renderArticle();

exit();

try {
    $article["body"] = parseBody($article["body"], $db, $auth, $m, $host);
}
catch (Exception $e){
    echo $e->getMessage();
}

echo renderArticle($article);
