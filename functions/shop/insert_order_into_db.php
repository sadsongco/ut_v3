<?php

function insertOrderIntoDB($order_details, $db) {
    try {
            $customer_id = checkIfCustomerExists($order_details['email'], $db); 
            if (!$customer_id) $customer_id = insertNewCustomer($order_details, $db);
            else updateCustomer($order_details, $customer_id, $db);
            $order_details['customer_id'] = $customer_id;
    }
    catch (Exception $e) {
            throw new Exception($e);
    }
    $db->beginTransaction();
    try {
            $order_details['order_id'] = insertOrderIntoOrderTable($order_details, $db);
            foreach ($order_details['items']['items'] as $order_item) {
                    insertItemIntoOrderTable($order_details, $order_item, $db);
            }
            foreach ($order_details['items']['bundles'] as $bundle) {
                    $order_bundle_id = insertBundleIntoOrderTable($order_details, $bundle, $db);
                    foreach ($bundle['items'] as $bundle_item) {
                            insertItemIntoOrderTable($order_details, $bundle_item, $db, $order_bundle_id);
                    }
            }

    } catch (Exception $e) {
            $db->rollback();
            error_log($e);
            exit("Database update failed: " . $e->getMessage());
    }
    // $db->rollback();
    $db->commit();
    $order_details['order_id'] = createOrderID($order_details['order_id'], $db);
    return $order_details;
}

function checkIfCustomerExists($email, $db) : int {
    try {
            $query = "SELECT customer_id FROM Customers WHERE email = ?";
            $result = $db->query($query, [$email])->fetch();
            if ($result) return $result['customer_id'];
            return false;
    } catch (Exception $e) {
            throw new Exception($e);
    }
}

function insertNewCustomer($order_details, $db) {
    try {
            $query = "INSERT INTO Customers VALUES (NULL, ?, ?, ?, ?, ?, ?, ?);";
            $params = [
                ucwords($order_details['name']),
                ucwords($order_details['delivery-address1']),
                ucwords($order_details['delivery-address2']),
                ucwords($order_details['delivery-town']),
                $order_details['delivery-postcode'],
                ucwords($order_details['delivery-country']),
                $order_details['email']
            ];
            $stmt = $db->query($query, $params);
            return $db->lastInsertId();
    } catch (Exception $e) {
            throw new Exception($e);
    }
}

function updateCustomer($order_details, $customer_id, $db) {
    try {
            $query = "UPDATE Customers SET address_1 = ?, address_2 = ?, city = ?, postcode = ?, country = ? WHERE customer_id = ?";
            $params = [
                ucwords($order_details['delivery-address1']),
                ucwords($order_details['delivery-address2']),
                ucwords($order_details['delivery-town']),
                $order_details['delivery-postcode'],
                ucwords($order_details['delivery-country']),
                $customer_id
            ];
            $stmt = $db->query($query, $params);
    }
    catch (Exception $e) {
            throw new Exception($e);
    }
}
function insertOrderIntoOrderTable($order_details, $db) {
    try {
    $query = "INSERT INTO New_Orders
        (customer_id,
        shipping_method,
        subtotal,
        shipping,
        vat,
        total,
        order_date,
        package_specs
        )
        VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
    $params = [
            $order_details['customer_id'],
            $order_details['shipping_method'],
            $order_details['totals']['subtotal'],
            $order_details['totals']['shipping'],
            $order_details['totals']['vat'],
            $order_details['totals']['total'],
            json_encode($order_details['package_specs'])
    ];
    $result = $db->query($query, $params);
    return $db->lastInsertId();
    } catch (Exception $e) {
            error_log($e);
            throw new Exception($e);
    }
}

function insertItemIntoOrderTable($order_details, $item, $db, $order_bundle_id = NULL) {
        $params = [
                $order_details['order_id'],
                $item['item_id'],
                !isset($item['option_id']) || !$item['option_id'] ? NULL : $item['option_id'],
                $item['quantity'],
                $item['price']
        ];
        $order_bundle_col = "";
        $order_bundle_val = "";
        if ($order_bundle_id) {
                $order_bundle_col = ", order_bundle_id";
                $order_bundle_val = ", ?";
                $params[] = $order_bundle_id;
        }
    try {
            $query = "INSERT INTO New_Order_items
            (
                order_id,
                item_id,
                option_id,
                quantity,
                order_price
                $order_bundle_col
            )
            VALUES (
            ?,
            ?,
            ?,
            ?,
            ?
            $order_bundle_val
            );";
            $db->query($query, $params);
    } catch (Exception $e) {
            throw new Exception($e);
    }
}

function insertBundleIntoOrderTable($order_details, $bundle, $db) {
        try {
                $query = "INSERT INTO Order_bundles VALUES (
                        NULL, ?, ?, ?, ?)";
                $params = [
                        $order_details['order_id'],
                        $bundle['bundle_id'],
                        $bundle['price'],
                        $bundle['quantity']
                ];
                $db->query($query, $params);
        } catch (Exception $e) {
                throw new Exception($e);
        }
        return $db->lastInsertId();
}

function createOrderID($order_id, $db)
{
    $date_str = $db->query("SELECT DATE_FORMAT(`order_date`, '%Y%m%d') FROM `New_Orders` WHERE `order_id` = ?", [$order_id])->fetch()['DATE_FORMAT(`order_date`, \'%Y%m%d\')'];
    return "$date_str-$order_id";
}
