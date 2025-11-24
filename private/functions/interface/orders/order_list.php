<?php

require_once(__DIR__."/includes/order_includes.php");

$filter = $_POST['orderFilter'] ?? null;

$filter_text = "WHERE New_Orders.rm_tracking_number IS NULL";
$area_filter = "";

switch ($filter) {
    case 'all':
        $filter_text = "";
        break;
    case 'failed':
        $filter_text = "WHERE New_Orders.transaction_id IS NULL";
        break;
    case 'submitted':
        $filter_text = "WHERE New_Orders.rm_order_identifier IS NOT NULL
        AND New_Orders.dispatched IS NULL";
        break;
    case 'dispatched':
        $filter_text = "WHERE New_Orders.rm_tracking_number IS NOT NULL";
        break;
    case 'new_uk':
        $filter_text = "WHERE New_Orders.rm_order_identifier IS NULL AND New_Orders.transaction_id IS NOT NULL";
        $area_filter = "AND Customers.country = 31";
        break;
    case 'new_usa':
        $filter_text = "WHERE New_Orders.rm_order_identifier IS NULL AND New_Orders.transaction_id IS NOT NULL";
        $area_filter = "AND Customers.country = 1";
        break;
    case 'new_row':
        $filter_text = "WHERE New_Orders.rm_order_identifier IS NULL AND New_Orders.transaction_id IS NOT NULL";
        $area_filter = "AND Customers.country != 1 AND Customers.country != 31";
        break;
    default:
        break;
}

try {
    $query = "SELECT
                    New_Orders.order_id,
                    CONCAT(DATE_FORMAT(New_Orders.order_date, '%y%m%d'), '-', New_Orders.order_id) AS disp_order_id,
                    New_Orders.transaction_id,
                    DATE_FORMAT(New_Orders.dispatched, '%e/%c/%y %k:%i') AS dispatched,
                    DATE_FORMAT(New_Orders.order_date, '%D %M %Y') AS order_date,
                    New_Orders.rm_order_identifier,
                    New_Orders.rm_tracking_number,
                    ROUND(New_Orders.subtotal, 2) AS subtotal,
                    ROUND(New_Orders.shipping, 2) AS shipping,
                    ROUND(New_Orders.vat, 2) AS vat,
                    ROUND(New_Orders.total, 2) AS total,
                    Customers.name,
                    Customers.address_1,
                    Customers.address_2,
                    Customers.city,
                    Customers.postcode,
                    Countries.name as country,
                    Customers.email,
                    Shipping_methods.service_name
                FROM New_Orders
                JOIN Shipping_methods ON New_Orders.shipping_method = Shipping_methods.shipping_method_id
                JOIN Customers ON New_Orders.customer_id = Customers.customer_id
                $area_filter
                JOIN Countries ON Customers.country = Countries.country_id
                $filter_text
                ORDER BY New_Orders.order_date DESC
            ;";
    $result = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result AS &$row) {
        $all_e_delivery = true;
        $row["items"] = getOrderItemData($row["order_id"], $db);
        foreach ($row["items"] as $item) {
            if (!$item["e_delivery"]) {
                $all_e_delivery = false;
            }
        }
        $bundle_query = "SELECT
                            Bundles.bundle_id,
                            Order_bundles.quantity,
                            Order_bundles.order_bundle_id,
                            FORMAT(Bundles.price, 2) AS price,
                            FORMAT(Bundles.price * Order_bundles.quantity, 2) AS bundle_total
                            FROM Order_bundles
                            LEFT JOIN Bundles ON Order_bundles.bundle_id = Bundles.bundle_id
                            WHERE Order_bundles.order_id = ?;";
        $row["bundles"] = $db->query($bundle_query, [$row["order_id"]])->fetchAll();
        foreach ($row["bundles"] as &$bundle) {
            $bundle["items"] = getOrderItemData($row["order_id"], $db, $bundle["order_bundle_id"]);
            foreach ($bundle["items"] as $item) {
                if (!$item["e_delivery"]) {
                    $all_e_delivery = false;
                }
            }
        }
        $row['all_e_delivery'] = $all_e_delivery;   
    }
}
catch (PDOException $e) {
    echo $e->getMessage();
}

$params["orders"] = $result;
$params["filter_target"] = ".orderContainer";
$params["num_orders"] = sizeof($result);
$params['force_download_token'] = FORCE_DOWNLOAD_TOKEN;

echo $m->render("orderList", $params);

function getOrderItemData($order_id, $db, $bundle_id = null) {
    $params = [];
    $cond = "IS NULL";
    $params[] = $order_id;
    if ($bundle_id) {
        $cond = "= ?";
        $params[] = $bundle_id;
    }
    $query = "SELECT
            Items.name,
            Items.e_delivery,
            New_Order_items.quantity,
            Item_options.option_name,
            FORMAT(New_Order_items.order_price, 2) AS price,
            FORMAT(New_Order_items.order_price * New_Order_items.quantity, 2) AS item_total
            FROM New_Order_items
            LEFT JOIN Items ON New_Order_items.item_id = Items.item_id
            LEFT JOIN Item_options ON New_Order_items.option_id = Item_options.item_option_id
            WHERE New_Order_items.order_id = ?
            AND New_Order_items.order_bundle_id $cond;";
    return $db->query($query, $params)->fetchAll();
}
