<?php

session_start();

include_once(__DIR__ . "/../../functions.php");
include(base_path("classes/Database.php"));
use Database\Database;
if (!isset($db)) $db = new Database('orders');

$query = "SELECT * FROM Shipping_methods WHERE shipping_method_id = ?";
$params = [$_POST['shippingMethod']];
$shipping_method = $db->query($query, $params)->fetch();

$_SESSION['shipping_method'] = $shipping_method;

header("HX-Trigger: shippingMethodUpdated");