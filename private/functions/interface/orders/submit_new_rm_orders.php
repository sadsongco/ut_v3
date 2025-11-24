<?php

include(__DIR__ . "/../../../../functions/functions.php");
include_once(__DIR__."/includes/order_includes.php");
include(base_path("classes/RoyalMail.php"));

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use RoyalMail\RoyalMail;

$country = "";

if (isset($_POST['order_zone'])) {
    switch ($_POST['order_zone']) {
        case 0:
            $country = "AND Customers.country = 31";
            break;
        case 1:
            $country = "AND Customers.country != 31 AND Customers.country != 1";
            break;
        case 2:
            $country = "AND Customers.country = 1";
            break;
    }
    
}


$params = [];
$order_cond = "";

if (isset($_GET['order_id'])) {
    $order_cond = "AND New_Orders.order_id = ?";
    $params[] = (int)$_GET['order_id'];
}

try {
    $query = "SELECT
        New_Orders.order_id,
        TRIM(New_Orders.shipping_method) AS shipping_method,
        New_Orders.shipping,
        New_Orders.subtotal,
        New_Orders.vat,
        New_Orders.total,
        New_Orders.order_date,
        New_Orders.mg,
        New_Orders.package_specs,
        Customers.name,
        Customers.address_1,
        Customers.address_2,
        Customers.city,
        Customers.postcode,
        Customers.country,
        Customers.email,
        Countries.rm_zone
    FROM New_Orders
    JOIN Customers ON New_Orders.customer_id = Customers.customer_id
        $country
    JOIN Countries ON Customers.country = Countries.country_id
    WHERE `rm_order_identifier` IS NULL
    AND `transaction_id` IS NOT NULL
    $order_cond
    ORDER BY New_Orders.order_date ASC
    LIMIT 2000";
    $orders = $db->query($query, $params)->fetchAll();
} catch (PDOException $e) {
    echo $e->getMessage(); 
}

if (sizeof($orders) === 0) {
    echo "No orders to submit from that zone";
    exit();
}

$ship_items = [];
foreach ($orders as &$order) {
    if ($order['mg'] != "" && !mgPackageName($order)) getMgPackageName($order, $db);
    $order['country_code'] = $order['country'];
    $order['items'] = getOrderItems($order, $db); // gets all items in an order whether bundled or not
    $order['weight'] = 0;
    $non_bundle_price = 0;
    $all_e_delivery = true;
    foreach ($order['items'] as &$item) {
        if (!$item['e_delivery']) $all_e_delivery = false;
        $item['weight'] = (int)$item['weight'];
        $item['weight'] *= 1000; // convert to grams from kg
        $order['weight'] += $item['weight'] * $item['quantity']; // total package weight
        $non_bundle_price += $item['quantity'] * $item['order_price'];
    }
    if ($all_e_delivery) continue;
    $order['bundle_discount'] = $non_bundle_price - $order['subtotal'];
    $rm = new RoyalMail($order['order_id'], $db);
    $rm->createRMOrder();
    $rm->submitRMOrder();
    $order_outcomes[] = $rm->getOrderOutcomes();
}



if (isset($_GET['order_id'])) {
    if (isset($order_outcomes[0][0]['data']->errors)) {
        echo "***FAILED***
        <div id='editOrder_" . $_GET['order_id'] . "' class='editOrderForm error' hx-swap-oob='true'>". $order_outcomes[0][0]['data']->errors[0]->errorMessage . "</div>";
        
    } else {
        echo "SUBMITTED
        <div id='editOrder_" . $_GET['order_id'] . "' class='editOrderForm' hx-swap-oob='true'>". $order_outcomes[0][0]['status'] . "</div>";
    }
    exit();
}
echo $m->render("orderOutcomes", ["outcomes"=>$order_outcomes]);

function getCountryCode($country, $db) {
    $query = "SELECT country_code FROM Countries WHERE name = ?";
    $params = [$country];
    $result = $db->query($query, $params)->fetch();
    return $result['country_code'];
}

function mgPackageName($order) {
    $package_specs = json_decode($order['package_specs']);
    return isset($package_specs->package_name);
}

function getMgPackageName($order, $db) {
    $package_specs = json_decode($order['package_specs'], true);
    try {
        $query = "SELECT package_id, name
        FROM Packages
        WHERE max_length_mm >= ?
        AND max_width_mm >= ?
        AND max_depth_mm >= ?
        AND max_weight_g >= ?
        AND zone = ?";
        $package_zone = $order['rm_zone'] == "UK" ? "UK" : "ROW";
        $params = [
            $package_specs['length'],
            $package_specs['width'],
            $package_specs['depth'],
            $package_specs['weight'],
            $package_zone
        ];
        $rm_package_specs = $db->query($query, $params)->fetch();
        $package_specs['package_id'] = $rm_package_specs['package_id'];
        $package_specs['package_name'] = $rm_package_specs['name'];
        $order['package_specs'] = json_encode($package_specs);
        $query = "UPDATE New_Orders SET package_specs = ? WHERE order_id = ?";
        $params = [$order['package_specs'], $order['order_id']];
        $db->query($query, $params);
    } catch (PDOException $e) {
        die($e->getMessage());
    }

}