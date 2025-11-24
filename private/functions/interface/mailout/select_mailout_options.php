<?php

require_once("includes/mailout_includes.php");

try {
    $query = "SELECT id, DATE_FORMAT(date, '%Y%m%d') AS `date` FROM mailouts ORDER BY date DESC";
    $mailouts = $db->query($query)->fetchAll();
} catch (PDOException $e) {
    exit("Couldn't retrieve mailouts: ".$e->getMessage());
}

echo $m->render("selectMailoutOptions", ["options"=>$mailouts]);