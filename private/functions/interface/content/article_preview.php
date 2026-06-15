<?php

include(__DIR__ . "/../../../../functions/functions.php");

use Database\Database;
$db = new Database('admin');
// templating
$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('views')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('views/partials'))
));

// articlebuilding
include(base_path('functions/interface/blog/get_article_media.php'));

$auth = [];

$host = getHost();
$article = $_POST;
try {
    $article["body"] = parseBody($article["body"], $db, $auth, $m, $host);
}
catch (Exception $e){
    echo $e->getMessage();
}

$article["preview"] = true;

echo $m->render('blog', ["article"=>$article]);

?>