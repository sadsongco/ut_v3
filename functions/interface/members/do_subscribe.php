<?php

include("../../functions.php");
require(base_path("classes/SUCheckout.php"));
require(base_path("functions/interface/members/get_sub_price.php"));

/* **
customer response when exists
stdClass Object
(
    [error_code] => CUSTOMER_ALREADY_EXISTS
    [instance] => c1c6000f-99c2-44bd-8ed5-a76de565ba9b
    [message] => Customer already exists
)
*/
use Database\Database;
$mem_db = new Database('members');
$db = new Database('orders');

$order_details = [...$_POST];
try {
    $country_code = $db->query("SELECT country_code FROM Countries WHERE country_id = ?", [$_POST['billing-country']])->fetch();
} catch (Exception $e) {
    error_log($e);
    exit("There has been an error. Please contact the webmaster.");
}

$order_details['billing-country-code'] = $country_code['country_code'];


$order_details['totals']['total'] = getSubPrice($order_details['subscription-level']);


$query = "SELECT * FROM Subscriptions WHERE user_id = ?";
try {
    $result = $mem_db->query($query, [$order_details['customer_id']])->fetchAll();
} catch (Exception $e) {
    error_log($e);
    exit("There has been an error. Please contact the webmaster.");
}

if (sizeof($result) !== 0 && $result[0]['status'] === $order_details['subscription-level']) {
    exit("You are already subscribed at this level. Thank you for the support!");
}

if (sizeof($result) !== 0) {
    if (!is_null($result[0]['status'])) echo "<p>You are changing your subscription level from " . $result[0]['status'] . " to " . $order_details['subscription-level'] . "</p>";
    $order_details['order_id'] = $result[0]['subscription_id'];
} else {
    $query = "INSERT INTO Subscriptions (user_id) VALUES (?)";
    try {
        $mem_db->query($query, [$order_details['customer_id']]);
    } catch (Exception $e) {
        error_log($e);
        exit("There has been an error. Please contact the webmaster.");
    }
    $order_details['order_id'] = $mem_db->lastInsertId();
}

use SUCheckout\SUCheckout;
try {
    $checkout = new SUCheckout($order_details, getHost());
}
catch (Exception $e) {
    error_log($e);
    exit("There has been an error. Please contact the webmaster.");
}

$response = $checkout->createCustomer()->getResponse();

if (isset($response->error_code)) {
    switch($response->error_code) {
        case "CUSTOMER_ALREADY_EXISTS":
            // do I need to handle anything here?
            break;
    }
}

$response = $checkout->createCheckout(true)->getResponse();
p_2($response);

if (isset($response->id)) {
    $query = "UPDATE Subscriptions SET su_checkout_id = ? WHERE subscription_id = ?";
    try {
        $mem_db->query($query, [$response->id, $order_details['order_id']]);
    } catch (Exception $e) {
        error_log($e);
        exit("There has been an error. Please contact the webmaster.");
    }
}

if (isset($response->error_code)) {
    switch($response->error_code) {
        case "DUPLICATED_CHECKOUT":
            echo "DUPLICATED CHECKOUT:";
            $query = "SELECT su_checkout_id FROM Subscriptions WHERE subscription_id = ?";
            $result = $mem_db->query($query, [$order_details['order_id']])->fetch();
            $checkout->setCheckoutId($result['su_checkout_id']);
            $response = $checkout->retrieveCheckout()->getResponse();
            p_2($response);
            break;
    }
}

exit();

