<?php

include_once (__DIR__ . "/../../../../../functions/functions.php");
require_once(base_path("classes/Database.php"));
use Database\Database;

if (!isset($db)) $db = new Database('mailing_list');

// Load Mustache
require_once(base_path('../lib/mustache.php-main/src/Mustache/Autoloader.php'));
Mustache_Autoloader::register();

if (!isset($m)) {
    $m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('/private/views/mailout/')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/mailout/partials/'))
    ));
}

function getMailoutData($id, $db) {
    try {
        $query = "SELECT * FROM mailouts WHERE id = ?";
        return $db->query($query, [$id])->fetch();
    }
    catch (PDOException $e) {
        throw new Exception("Problem retrieving mailout data: ".$e->getMessage());
    }
}

function getCurrentMailout($db, $id)
{
    try {
        $query = "SELECT DATE_FORMAT(date, '%Y%m%d') AS `date` FROM mailouts WHERE id = ?";
        $result = $db->query($query, [$id])->fetch();
        return $result['date'];
    } catch (PDOException $e) {
        exit("Couldn't retrieve current mailout: ".$e->getMessage());
    }
}
