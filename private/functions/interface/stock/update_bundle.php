<?php

require(__DIR__ . "/../../../../functions/functions.php");
require(base_path("classes/Database.php"));

use Database\Database;
$db = new Database('orders');

$active = isset($_POST['active']) ? 1 : 0;
$query = "UPDATE Bundles SET price = ?,active = ? WHERE bundle_id = ?";
try {
    $db->query($query, [$_POST['price'],$active, $_POST['bundle_id']]);
}
catch (Exception $e) {
    echo $e->getMessage();
}

echo "<h1>Bundle Updated</h1>";