<?php

require_once(__DIR__."/includes/order_includes.php");

try {
    $query = "DELETE FROM New_Orders WHERE order_id = ?";
    $db->query($query, [$_GET['order_id']]);
}
catch (PDOException $e) {
    echo $e->getMessage();
}

header ('HX-Trigger:updateOrderList');
