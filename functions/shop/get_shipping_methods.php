<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getShippingMethods($zone, $db)
{
    try {
        $query = "SELECT
            Shipping_methods.shipping_method_id,
            Shipping_methods.service_name,
            Shipping_methods.service_code
        FROM Shipping_prices
        INNER JOIN Shipping_methods ON Shipping_methods.shipping_method_id = Shipping_prices.shipping_method_id
        WHERE Shipping_prices.rm_zone = ?
        AND Shipping_prices.min_weight_g <= ?
        AND Shipping_prices.max_weight_g >= ?
        GROUP BY Shipping_methods.shipping_method_id";
        $params = [
            $zone,
            $_SESSION['package_specs']['weight'],
            $_SESSION['package_specs']['weight']
        ];
        return $db->query($query, $params)->fetchAll();
    
    } catch (PDOException $e) {
        throw new Exception($e);
    }
}