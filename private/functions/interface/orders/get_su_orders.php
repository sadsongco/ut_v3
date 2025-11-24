<?php

include(__DIR__ . "/../../../../functions/functions.php");

require(base_path("private/classes/EmailParser.php"));
include(__DIR__ . "/includes/order_includes.php");

use Parser\EmailParser;

$dom = new DOMDocument();
if ($mbox=imap_open( IMAP_CONFIG::AUTHHOST, IMAP_CONFIG::USERNAME, IMAP_CONFIG::PASSWORD )) {
        $output = "Processing orders...<br>";
    $headers = imap_headers($mbox);
    $msgs = imap_check($mbox);
    if ($msgs->Nmsgs == 0) {
                exit("No new orders");
        }
    $headers = imap_fetch_overview($mbox,"1:{$msgs->Nmsgs}",0);
    foreach ($headers as $id=>$header) {
                $subject = imap_mime_header_decode($header->subject);
                if (strtolower($subject[0]->text) != "new order") continue;
                $message = imap_fetchbody($mbox, $id + 1, '2');
                $message = imap_utf8(imap_qprint($message));
                try {
                        $orderDetailObj = new EmailParser($message, $id);
                        $orderDetailObj->parse();
                        $orderDetailObj->rearrayItems();
                        $order_details = $orderDetailObj->get();
                        try {
                                $missing_info = insertOrderIntoDatabase($order_details, $db);
                        } catch (Exception $e) {
                                error_log($e);
                                $output .= "Couldn't insert order " . $order_details['order_no'] . " into database: " . $e->getMessage() . "<br>";
                                continue;
                        }
                        $output .= "Order " . $order_details['order_no'] . " inserted into database.<br>";
                        if ($missing_info) $output .= "THIS ORDER IS MISSING INFO.<br>";
                        imap_delete($mbox, $id + 1);
                        $output .= "Email for order " . $order_details['order_no'] . " deleted.<br>";
                }
                catch (Exception $e) {
                        error_log($e);
                        $output .= $e->getMessage() . "<br>";
                }
        }
        imap_close($mbox, CL_EXPUNGE);
        header ('HX-Trigger:updateOrderList');
        echo $output;
} else {
        for ($id = 1; $id < 1000; $id++) {
                if (!$message) break;
                $orderDetailObj = new EmailParser($message, $id);
                $orderDetailObj->parse();
                $order_details = $orderDetailObj->get();        }
}

function insertOrderIntoDatabase($order_details, $db) {
        $missing_info = false;
        try {
                if ($order_details['country'] == "United States") {
                        $order_details['country'] = "USA";
                        $zip = getZipFromPostcode($order_details['postcode'], 'us');
                        if (empty($zip)) $missing_info = true;
                        else {
                                $order_details['postcode'] = $zip['places'][0]['state abbreviation'] . " " . $zip['post code'];
                                $order_details['town'] = $zip['places'][0]['place name'];
                        }
                }
                
                if ($order_details['town'] == "") {
                        $query = "SELECT country_code FROM Countries WHERE name = ?";
                        $result = $db->query($query, [$order_details['country']])->fetch();

                        $country_code = $result['country_code'];
                        if ($country_code != "") {
                                $zip = getZipFromPostcode($order_details['postcode'], $country_code);
                                if (empty($zip)) $missing_info = true;
                                else {
                                        $order_details['postcode'] = $zip['post code'];
                                        $order_details['town'] = $zip['places'][0]['place name'];
                                }
                        }
                }
                $customer_id = checkIfCustomerExists($order_details['email'], $db); 
                if (!$customer_id) $customer_id = insertNewCustomer($order_details, $db);
                else updateCustomer($order_details, $customer_id, $db);
                $order_details['customer_id'] = $customer_id;
                foreach ($order_details['items'] as &$item) {
                    $item_id = checkIfItemExists($item['item'], $db);
                    if (!$item_id) {
                        $item_id = insertNewItem($item['item'], $item['price'], $db);
                        $item['item_id'] = $item_id;
                    }
                    else $item['item_id'] = $item_id;
                }
        }
        catch (Exception $e) {
                throw new Exception($e);
        }
        $db->beginTransaction();
        try {
                $order_details['order_id'] = insertOrderIntoOrderTable($order_details, $db);
                foreach ($order_details['items'] as $order_item) {
                        insertItemIntoOrderTable($order_details, $order_item, $db);
                }

        } catch (Exception $e) {
                $db->rollback();
                error_log($e);
                exit("Database update failed: " . $e->getMessage());
        }
        // $db->rollback();
        $db->commit();
        return $missing_info;
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
                $query = "INSERT INTO Customers VALUES (NULL, ?, ?, NULL, ?, ?, ?, ?);";
                $params = [
                        ucwords($order_details['name']),
                        ucwords($order_details['address']),
                        ucwords($order_details['town']),
                        $order_details['postcode'],
                        ucwords($order_details['country']),
                        $order_details['email']
                ];
                $db->query($query, $params);
                return $db->lastInsertId();
        } catch (Exception $e) {
                throw new Exception($e);
        }
}

function updateCustomer($order_details, $customer_id, $db) {
        try {
                $query = "UPDATE Customers SET address_1 = ?, city = ?, postcode = ?, country = ? WHERE customer_id = ?";
                $params = [
                        ucwords($order_details['address']),
                        ucwords($order_details['town']),
                        $order_details['postcode'],
                        ucwords($order_details['country']),
                        $customer_id
                ];
                $db->query($query, $params);
        }
        catch (Exception $e) {
                throw new Exception($e);
        }
}

function checkIfItemExists($item_name, $db) {
    try {
        $query = "SELECT item_id FROM Items WHERE name LIKE ?";
        $params = ['%'. trim($item_name) . '%'];
        $result = $db->query($query, $params)->fetch();
        if (!$result) return false;
        return $result['item_id'];
    }
    catch (Exception $e) {
        throw new Exception($e);
    }
}

function insertNewItem($item_name, $item_price, $db) {
    try {
        $query = "INSERT INTO Items VALUES (NULL, ?, ?)";
        $params = [$item_name, $item_price];
        $db->query($query, $params);
        return $db->lastInsertId();
    } catch (Exception $e) {
        throw new Exception($e);
    }
}

function updateItem($item_id, $item_price, $db) {
    try {
        $query = "UPDATE Items SET price = ? WHERE item_id = ?";
        $params = [$item_price, $item_id];
        $db->query($query, $params);
        return true;
    } catch (Exception $e) {
        throw new Exception($e);
    }
}

function insertOrderIntoOrderTable($order_details, $db) {
        try {
        $query = "INSERT INTO Orders (
        sumup_id,
        customer_id,
        shipping_method,
        subtotal,
        shipping,
        vat,
        total,
        order_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [
                $order_details['order_no'],
                $order_details['customer_id'],
                $order_details['postage_method'],
                $order_details['totals']['Subtotal'],
                $order_details['totals']['Shipping'],
                $order_details['totals']['vat'],
                $order_details['totals']['total'],
                $order_details['order_date']
        ];
        $db->query($query, $params);
        return $db->lastInsertId();
        } catch (Exception $e) {
                error_log($e);
                throw new Exception($e);
        }
}

function insertItemIntoOrderTable($order_details, $item, $db) {
        try {
                $query = "INSERT INTO Order_items
                (
                        order_id,
                        item_id,
                        amount, 
                        order_price
                )
                VALUES (
                        ?,
                        ?,
                        ?,
                        ?
                );";
                $params = [
                        $order_details['order_id'],
                        $item['item_id'],
                        $item['amount'],
                        $item['price']
                ];
                $db->query($query, $params);
        } catch (Exception $e) {
                throw new Exception($e);
        }
}

function get_numeric($val) {
        if (is_numeric($val)) {
          return $val + 0;
        }
        return 0;
      }

function getZipFromPostcode($postcode, $country_code='us') {
        preg_match('/\d{5}(-\d{4})?\b/', $postcode, $zip);
        $endpoint = "https://api.zippopotam.us/$country_code/";
        $ch = curl_init($endpoint . $zip[0]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $place_details = json_decode($response, true);
        if (empty($place_details)) {
                $shortzip = str_replace($zip[1], "", $zip[0]);
                $ch = curl_init($endpoint . $shortzip);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);
                $place_details = json_decode($response, true);
        }
        return $place_details;
}