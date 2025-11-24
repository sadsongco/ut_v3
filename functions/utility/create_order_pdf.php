<?php

function createOrderPDF($order_id, $db) {
    try {
        $query = "SELECT CONCAT(DATE_FORMAT(New_Orders.order_date, '%y%m%d'), '-', New_Orders.order_id) AS order_id, New_Orders.subtotal, New_Orders.vat, New_Orders.total,
                        Customers.name, Customers.address_1, Customers.address_2, Customers.city, Customers.postcode, Countries.name as country,
                        DATE_FORMAT(New_Orders.order_date, '%D %M %Y') AS order_date,
                        New_Orders.shipping, New_Orders.shipping_method
                    FROM New_Orders
                    LEFT JOIN Customers ON New_Orders.customer_id = Customers.customer_id
                    LEFT JOIN Countries ON Customers.country = Countries.country_id
                    WHERE New_Orders.order_id = ?
            ;";
            $result = $db->query($query, [$order_id])->fetch();
            $result["items"] = getOrderItemData($order_id, $db);
            $query = "SELECT *, FROM Bundles LEFT JOIN Order_bundles ON Bundles.bundle_id = Order_bundles.bundle_id WHERE order_id = ?";
            $query = "SELECT Bundles.bundle_id,
                Order_bundles.quantity,
                Order_bundles.order_bundle_id,
                FORMAT(Order_bundles.order_bundle_price, 2) AS price,
                FORMAT(Order_bundles.order_bundle_price * Order_bundles.quantity, 2) AS bundle_total
                FROM Order_bundles
                LEFT JOIN Bundles ON Order_bundles.bundle_id = Bundles.bundle_id
                WHERE Order_bundles.order_id = ?";
            $result["bundles"] = $db->query($query, [$order_id])->fetchAll();
            foreach ($result["bundles"] as &$bundle) {
                $bundle["items"] = getOrderItemData($order_id, $db, $bundle["order_bundle_id"]);
            }
        
    }
    
    catch (PDOException $e) {
        echo $e->getMessage();
    }
    
    return makeOrderPDF($result, 'F', base_path(ORDER_PDF_PATH));
}

function getOrderItemData($order_id, $db, $order_bundle_id = null)
{
    $params = [$order_id];
    $price_cond = ", FORMAT(New_Order_items.order_price * New_Order_items.quantity, 2) AS item_total,
                FORMAT(New_Order_items.order_price, 2) AS price";
    $bundle_cond = " IS NULL";
    if ($order_bundle_id) {
        $price_cond = "";
        $bundle_cond = " = ?";
        $params[] = $order_bundle_id;
    }
    $query = "SELECT Items.name, 
                New_Order_items.quantity,
                Item_options.option_name
                $price_cond
                FROM New_Order_items
                LEFT JOIN Items ON New_Order_items.item_id = Items.item_id
                LEFT JOIN Item_options ON New_Order_items.option_id = Item_options.item_option_id
                WHERE New_Order_items.order_id = ?
                AND New_Order_items.order_bundle_id $bundle_cond;";
    return $db->query($query, $params)->fetchAll();
}