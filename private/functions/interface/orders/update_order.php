<?php
require_once(__DIR__."/includes/order_includes.php");

extract($_POST);

if ($submit == "cancel") {
    exit("<script>
        document.getElementById('order_$order_id').scrollIntoView({behaviour: 'smooth', block: 'start'});
    </script>");
}

try {
    $query = "UPDATE Orders
    SET
    shipping_method = ?,
    subtotal = ?,
    shipping = ?,
    vat = ?,
    total = ?
    WHERE order_id = ?";
    $params = [
        $shipping_method,
        $subtotal,
        $shipping,
        $vat,
        $total,
        $order_id
    ];
    $stmt = $db->query($query, $params);

    $query = "UPDATE Customers
    SET
    name = ?,
    email = ?,
    address_1 = ?,
    address_2 = ?,
    city = ?,
    postcode = ?,
    country = ?
    WHERE customer_id = ?";
    $params = [
        $name,
        $email,
        $address_1,
        $address_2,
        $city,
        $postcode,
        $country,
        $customer_id
    ];
    $stmt = $db->query($query, $params);
} catch(Exception $e) {
    echo "couldn't update order: ".$e->getMessage();
    exit();
}

header ('HX-Trigger:updateOrderList');

echo "<p>Order $order_id Updated</p>";