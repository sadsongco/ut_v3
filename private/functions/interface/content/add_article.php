<?php
include(__DIR__ . "/../../../../functions/functions.php");
require_once(__DIR__."/includes/privateIncludes.php");


function deleteArticle() {
    $query = "DELETE FROM articles WHERE article_id = ?;";
    $params = [$_POST['article_id']];
    $msg = "Article deleted";
    return [$query, $params, $msg];
}

function updateArticle() {
    $query = "UPDATE articles SET
        title=?,
        body=?,
        added=?,
        tab=?,
        draft=?,
        posted_by=?
    WHERE article_id = ?
    ;";
    $params = [
        $_POST['title'],
        $_POST['body'],
        $_POST['articleDate'],
        $_POST['tab'],
        isset($_POST['draft']) ? 1 : 0,
        $_POST['posted_by'],
        $_POST['article_id']
    ];
    $msg = "Article Updated";
    return [$query, $params, $msg];
}

function insertArticle($article_date) {
    $query = "INSERT INTO articles VALUES (0, ?, ?, ?, ?, ?, ?);";
    $params = [
        $_POST['title'],
        $_POST['body'],
        $article_date,
        $_POST['tab'],
        isset($_POST['draft']) ? 1 : 0,
        $_POST['posted_by']
    ];
    $msg = "Article saved";
    return [$query, $params, $msg];
}

if (!isset($_POST['articleDate'])) $_POST['articleDate'] = $article_date;

try {
    $article_date = date("Y-m-d H:i:s");
    if (isset($_POST['delete_article'])) {
        $query_arr = deleteArticle();
    }elseif (isset($_POST['article_id'])) {
        $query_arr = updateArticle();
    } else {
        $query_arr = insertArticle($_POST['articleDate']);
    }
    $db->query($query_arr[0], $query_arr[1]);
}
catch (PDOException $e) {
    die ($e->getMessage());
}

$content = [];
$tabs = getTabs($db);
$posters = getPosters($db);

$msg = ["msg"=>$query_arr[2]];
if (isset($_POST['draft'])) {
    $msg = ["msg"=>"Draft saved"];
    if (!isset($_POST['articleDate'])) $_POST['articleDate'] = $article_date;
    $content = $_POST;
    foreach ($tabs as &$tab) {
        if (intval($tab['tab_id']) == intval($content['tab'])) $tab['selected'] = 1;
    }
    foreach($posters as &$poster) {
        if ($poster['name'] == $content['posted_by']) $poster['selected'] = 1;
    }
}

header ('HX-Trigger:refreshArticleList');
echo $m->render("articleForm", [
    "default_date"=>date('Y-m-d\TH:i'),
    "message"=>$msg,
    "content"=> $content,
    "tabs"=>$tabs,
    "posters"=>$posters
]);
