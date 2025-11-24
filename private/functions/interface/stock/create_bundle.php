<?php

require(__DIR__ . "/../../../../functions/functions.php");
require(base_path("classes/Database.php"));

use Database\Database;
$db = new Database('orders');

$db->beginTransaction();

$query = "INSERT INTO Bundles VALUES (null, NOW(), ?, 1)";
$db->query($query, [$_POST['price']]);
$bundle_id = $db->lastInsertId();

foreach ($_POST['bundle-item'] as $bundle_item) {
    $query = "INSERT INTO Bundle_items VALUES (null, ?, ?)";
    $db->query($query, [$bundle_id, $bundle_item]);
}

$db->commit();

echo "Bundle Created";