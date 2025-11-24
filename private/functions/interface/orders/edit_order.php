<?php

include(__DIR__ . "/includes/order_includes.php");

extract($_GET);

try {
    $query = "SELECT
    order_id,
    shipping_method,
    subtotal,
    shipping,
    vat,
    total,
    Customers.customer_id,
    name,
    email,
    address_1,
    address_2,
    city,
    postcode,
    country
    FROM New_Orders
    JOIN Customers ON New_Orders.customer_id = Customers.customer_id
    WHERE order_id = ?";
    $order = $db->query($query, [$order_id])->fetch();
} catch (Exception $e) {
    header ('HX-Trigger:editOrder');
    echo "Couldn't get order details from database: " .$e->getMessage();
    exit();
}

$params = [];

foreach ($order as $key => $value) {
    $type = "text";
    if (is_numeric($value)) {
        $type = "number";
    }
    $label = ucwords(str_replace("_", " ", $key));
    $params[] = ["key"=>$key, "value"=>$value, "type"=>$type, "label"=>$label];
}

// header ('HX-Trigger:editOrder');
echo $m->render("editOrder", ["orders"=>$params]);
