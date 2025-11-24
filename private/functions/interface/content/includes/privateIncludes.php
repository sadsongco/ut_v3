<?php

include(__DIR__ . "/../../../../../functions/functions.php");
require(base_path("classes/Database.php"));
use Database\Database;
$db = new Database('admin');

// templating
require base_path('../lib/mustache.php-main/src/Mustache/Autoloader.php');
Mustache_Autoloader::register();

$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/content/')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/content/partials/'))
));

function getTabs($db) {
    try {
        $query = "SELECT * FROM tabs";
        return $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }
    catch (PDOException $e) {
        throw new Exception ($e->getMessage());
    }
}

function getPosters($db) {
    try {
        $query = "SELECT column_type FROM information_schema.columns WHERE table_name = 'articles' AND column_name = 'posted_by';";
        $result = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
        $column = array_key_exists("COLUMN_TYPE", $result[0]) ? "COLUMN_TYPE" : "column_type";
        $result_str = str_replace(array("enum('", "')", "''"), array('', '', "'"), $result[0][$column]);
        $arr = explode("','", $result_str);
        if (sizeof($arr) == 0) $arr = ["Nigel", "Andy", "Jason", "Admin"];
        $posters = [];
        foreach ($arr as $poster) {
            $posters[] = ["name"=>$poster];
        }
        return $posters;
    }
    catch (PDOException $e) {
        throw new Exception ($e->getMessage());
    }
}

?>