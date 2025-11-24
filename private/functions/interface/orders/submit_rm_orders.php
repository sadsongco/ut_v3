<?php

include(__DIR__ . "/../../../../functions/functions.php");
include_once(__DIR__."/includes/order_includes.php");
include(base_path("classes/RoyalMail.php"));

use RoyalMail\RoyalMail;

try {
    $query = "SELECT
        Orders.order_id,
        TRIM(Orders.shipping_method) AS shipping_method,
        Orders.shipping,
        Orders.subtotal,
        Orders.vat,
        Orders.total,
        Orders.order_date,
        Customers.name,
        Customers.address_1,
        Customers.address_2,
        Customers.city,
        Customers.postcode,
        Customers.country,
        Customers.email
    FROM Orders
    LEFT JOIN Customers ON Orders.customer_id = Customers.customer_id
    WHERE `label_printed` = 0 OR `label_printed` IS NULL
    ORDER BY Orders.order_date ASC
    LIMIT 2000";
    $orders = $db->query($query)->fetchAll();
} catch (PDOException $e) {
    echo $e->getMessage(); 
}


$ship_items = [];
foreach ($orders as &$order) {
    $order['country_code'] = getCountryCode($order['country'], $db);
    $order['items'] = getOrderItems($order, $db);
    $order['weight'] = 0;
    foreach ($order['items'] as &$item) {
        $item['weight'] *= 1000; // convert to grams from kg
        $order['weight'] += $item['weight'] * $item['amount']; // total package weight
    }
    $rm = new RoyalMail($order['order_id'], $db, true);
    $rm->createRMOrder();
    $rm->submitRMOrder();

    $order_outcomes[] = $rm->getOrderOutcomes();

    
    echo $m->render("orderOutcomes", ["outcomes"=>$order_outcomes]);
}




function getOrderItems($order, $db) {

    try {
        $query = "SELECT
            Order_items.order_price,
            Order_items.amount,
            Items.*
        FROM Order_items
        JOIN Items ON Order_items.item_id = Items.item_id
        WHERE Order_items.order_id = ?";
        $params = [$order["order_id"]];
        return $db->query($query, $params)->fetchAll();
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
}

function getCountryCode($country, $db) {
    $query = "SELECT country_code FROM Countries WHERE name = ?";
    $params = [$country];
    $result = $db->query($query, $params)->fetch();
    return $result['country_code'];
}