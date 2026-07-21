<?php

include("../../functions.php");
require(base_path("classes/SUCheckout.php"));
require(base_path("functions/interface/members/get_sub_price.php"));

use SumUp\SumUp;
use SumUp\Exception\SDKException;

$sumup = new SumUp();

use Database\Database;
$mem_db = new Database('members');
$db = new Database('orders');

function getOrderDetails($order_id, $mem_db, $auth) {
    $query = "SELECT su_checkout_id, su_customer_id FROM Subscriptions WHERE subscription_id = ? AND user_id = ?";
    try {
        $result = $mem_db->query($query, [$order_id, $auth->getUserId()])->fetch();
    } catch (Exception $e) {
        error_log($e);
        exit("There has been an error. Please contact the webmaster.");
    }
    if (!$result) {
        exit("There has been an error. Please contact the webmaster.");
    }
    return [
        "order_id"=>$order_id,
        "su_checkout_id"=>$result['su_checkout_id'],
        "su_customer_id"=>$result['su_customer_id']
    ];
}

if (isset($_POST['make_payment'])) {
    if (!isset($_POST['order_id']) || !is_numeric($_POST['order_id']) || !isset($_POST['subscription_level'])) exit("There has been an error. Please contact the webmaster.");
    $order_id = $_POST['order_id'];
    $order_details = getOrderDetails($order_id, $mem_db, $auth);
    [$order_details['totals']['subtotal'], $order_details['totals']['vat'], $order_details['totals']['total']] = getSubPrice($_POST['subscription_level']);
    $order_details['subscription_level'] = $_POST['subscription_level'];
    $order_details['disp_total'] = number_format($order_details['totals']['total'], 2);
    $order_details['order_name'] = $_POST['name'];
    // p_2($order_details);
    echo $m->render("members/payment", ["order_details"=>$order_details]);
    // $order_details = [...$_POST];
    // unset($order_details['make_payment']);
    // [$order_details['totals']['subtotal'], $order_details['totals']['vat'], $order_details['totals']['total']] = getSubPrice($order_details['subscription-level']);
    // if ($order_details['totals']['total'] != $order_details['total']) {
    //     exit("There has been an error. Please contact the webmaster.");
    // }
    // $query = "SELECT su_checkout_id FROM Subscriptions WHERE subscription_id = ? AND user_id = ?";
    // try {
    //     $result = $mem_db->query($query, [$order_details['order_id'], $auth->getUserId()])->fetch();
    // } catch (Exception $e) {
    //     error_log($e);
    //     exit("There has been an error. Please contact the webmaster.");
    // }
    // if (!$result) {
    //     exit("There has been an error. Please contact the webmaster.");
    // }
    // $items = [
    //     [
    //     "item_id"=>99991,
    //     "name"=>$order_details['subscription-level']. " Subscription",
    //     "price"=>$order_details['totals']['total']
    //     ]
    // ];
    // p_2($order_details);
    // p_2($result);

    // echo $m->render('members/payment', [
    //     "checkout_id"=>$result['su_checkout_id'],
    //     "order_id"=>$order_details['order_id'],
    //     "name"=>$order_details['name'],
    //     "items"=>$items,
    //     "subtotal"=>$order_details['totals']['subtotal'],
    //     "shipping"=>0,
    //     "vat"=>$order_details['totals']['vat'],
    //     "amount"=>$order_details['totals']['total']
    // ]);
    exit();
}

$order_details = [...$_POST];
$payment_message = [];

try {
    $country_code = $db->query("SELECT country_code FROM Countries WHERE country_id = ?", [$_POST['billing-country']])->fetch();
} catch (Exception $e) {
    error_log($e);
    exit("There has been an error. Please contact the webmaster.");
}

$order_details['billing-country-code'] = $country_code['country_code'];


[$order_details['totals']['subtotal'], $order_details['totals']['vat'], $order_details['totals']['total']] = getSubPrice($order_details['subscription-level']);

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
    if (!is_null($result[0]['status'])) $payment_message[] =  "You are changing your subscription level from " . $result[0]['status'] . " to " . $order_details['subscription-level'] . " with a new monthly payment of &pound;" . number_format($order_details['totals']['total'], 2);
    else $payment_message[] =  "You are subscribing at " . $order_details['subscription-level'] . " level with a monthly payment of &pound;" . number_format($order_details['totals']['total'], 2);
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

$encrypted_id = openssl_encrypt($order_details['customer_id'], SU_ENCRYPTION_CIPHER, SU_ENCRYPTION_KEY, false, SU_ENCRYPTION_IV);

try {
$customer = $sumup->customers()->create([
            'customer_id' => $encrypted_id,
            'personal_details' => [
                'first_name'=>$order_details['name'], 
                'last_name'=>$order_details['name'],
                'email'=>$order_details['email'],
                'address'=>[
                    'city'=>$order_details['billing-town'],
                    'country'=>$order_details['billing-country-code'],
                    'line1'=>$order_details['billing-address1'],
                    'line2'=>$order_details['billing-address2'],
                    'postal_code'=>$order_details['billing-postcode']
                ]
            ]
        ]);
} catch (\SumUp\Exception\ApiException $e) {
    if (!$e->getResponseBody()->errorCode === "CUSTOMER_ALREADY_EXISTS") {
        error_log($e);
        error_log(json_encode($e->getResponseBody(), JSON_PRETTY_PRINT) . "\n");
        exit("There has been an error. Please contact the webmaster.");
    }
}

$order_details['su_customer_id'] = $encrypted_id;
$query = "UPDATE Subscriptions SET su_customer_id = ? WHERE subscription_id = ?";
try {
    $mem_db->query($query, [$order_details['su_customer_id'], $order_details['order_id']]);
} catch (Exception $e) {
    error_log($e);
    exit("There has been an error. Please contact the webmaster.");
}

// $response = $checkout->createCustomer()->getResponse();

if (isset($response->error_code)) {
    switch($response->error_code) {
        case "CUSTOMER_ALREADY_EXISTS":
            // do I need to handle anything here?
            break;
    }
}

try {
    $checkout = $sumup->checkouts()->create(new \SumUp\Types\CheckoutCreateRequest(
        checkoutReference: $order_details['order_id'],
        amount: $order_details['totals']['total'],
        currency: 'GBP',
        merchantCode: SU_MERCHANT_CODE,
        description: "Subscription",
        purpose: 'SETUP_RECURRING_PAYMENT',
        customerId: $order_details['su_customer_id']
    ));
}
catch (\SumUp\Exception\ApiException $e) {
    if ($e->getResponseBody()->errorCode === "DUPLICATED_CHECKOUT") {
        $query = "SELECT su_checkout_id FROM Subscriptions WHERE subscription_id = ? AND user_id = ?";
        try {
            $result = $mem_db->query($query, [$order_details['order_id'], $auth->getUserId()])->fetch();
        } catch (Exception $e) {
            error_log($e);
            exit("There has been an error. Please contact the webmaster.");
        }
        if (!$result) {
            exit("There has been an error. Please contact the webmaster.");
        }
        if (!$result) {
            error_log("No stored checkout id for duplicated checkout");
            exit("There has been an error. Please contact the webmaster");
        }
        $checkout = $sumup->checkouts()->get($result['su_checkout_id']);
    } else {
        error_log($e);
        error_log(json_encode($e->getResponseBody(), JSON_PRETTY_PRINT) . "\n");
        exit("There has been an error. Please contact the webmaster.");
    }
}
catch (\SumUp\Exception\UnexpectedApiException $e) {
    error_log($e);
    exit("There has been an error. Please contact the webmaster.");
}
catch (\SumUp\Exception\SDKException $e) {
    error_log($e);
    exit("There has been an error. Please contact the webmaster.");
}

$query = "UPDATE Subscriptions SET su_checkout_id = ? WHERE subscription_id = ?";
try {
    $mem_db->query($query, [$checkout->id, (int)$checkout->checkoutReference]);
} catch (PDOException $e) {
    error_log($e);
    exit("There has been an error. Please contact the webmaster.");
}

$order_details['su_checkout_id'] = $checkout->id;
$order_details['total_disp'] = number_format($order_details['totals']['total'], 2);
if ((int)date('j') > 28) $order_details['sub_day_near'] = true;
$order_details['sub_day_disp'] = date('jS');

// p_2($order_details);
// p_2($_SERVER['HTTP_USER_AGENT']);
// p_2(getenv("REMOTE_ADDR"));
echo $m->render("members/confirm_subscribe", ["order_details"=>$order_details]);