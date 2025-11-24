<?php


require_once(__DIR__ . "/../functions/functions.php");

// load mustache for all controllers
require base_path('../lib/mustache.php-main/src/Mustache/Autoloader.php');
Mustache_Autoloader::register();
$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('views')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('views/partials'))
));

require(base_path("classes/Database.php"));
use Database\Database;
$db = new Database('content');

include(base_path("functions/interface/blog/get_article_media.php"));

$auth = [];

$host = getHost();

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
$article["body"] = parseBody($article["body"], $db, $auth, $m, $host);
$article["username"] = "USER";
$article["tab_id"] = 1; //VALIDATE POST below?
$article["show_comments"] = isset($_POST['show_comments']) ? true : false;
$article["host"] = $host;
if (isset($_POST['hide'])) $article["hide"] = $_POST['hide'];
if ($article["tab_name"] == "blogs") $article["blog"] = true;

echo $m->render("articles/article", $article);
