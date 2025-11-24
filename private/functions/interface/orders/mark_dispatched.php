<?php

include(__DIR__ . "/../../../../functions/functions.php");
include("includes/order_includes.php");

try {
    $query = !isset($_GET['undo']) ?
    "UPDATE New_Orders
    SET
        dispatched = NOW(),
        rm_order_identifier = 1,
        rm_created = NOW(),
        rm_tracking_number = 'e_delivery'
    WHERE order_id = ?"
    :
    "UPDATE New_Orders
    SET
        dispatched = NULL,
        rm_order_identifier = NULL,
        rm_created = NULL,
        rm_tracking_number = NULL
    WHERE order_id = ?";
    $params = [$_GET["order_id"]];
    $db->query($query, $params);
} catch (PDOException $e) {
    echo $e->getMessage();
}

echo !isset($_GET['undo']) ?"Marked Dispatched" : "Marked Not Dispatched";