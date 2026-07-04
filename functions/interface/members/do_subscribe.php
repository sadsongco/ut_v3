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

if (isset($_POST['make_payment'])) {
    $order_details = [...$_POST];
    unset($order_details['make_payment']);
    [$order_details['totals']['subtotal'], $order_details['totals']['vat'], $order_details['totals']['total']] = getSubPrice($order_details['subscription-level']);
    if ($order_details['totals']['total'] != $order_details['total']) {
        exit("There has been an error. Please contact the webmaster.");
    }
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
    $items = [
        [
        "item_id"=>99991,
        "name"=>$order_details['subscription-level']. " Subscription",
        "price"=>$order_details['totals']['total']
        ]
    ];
    p_2($order_details);
    p_2($result);

    echo $m->render('members/payment', [
        "checkout_id"=>$result['su_checkout_id'],
        "order_id"=>$order_details['order_id'],
        "name"=>$order_details['name'],
        "items"=>$items,
        "subtotal"=>$order_details['totals']['subtotal'],
        "shipping"=>0,
        "vat"=>$order_details['totals']['vat'],
        "amount"=>$order_details['totals']['total']
    ]);
    exit("MAKE PAYMENT");
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
    p_2($e->getResponseBody()->errorCode === "CUSTOMER_ALREADY_EXISTS");
    if (!$e->getResponseBody()->errorCode === "CUSTOMER_ALREADY_EXISTS") {
        error_log($e);
        error_log(json_encode($e->getResponseBody(), JSON_PRETTY_PRINT) . "\n");
        exit("There has been an error. Please contact the webmaster.");
    }
}

$order_details['customer_su_id'] = $encrypted_id;

// $response = $checkout->createCustomer()->getResponse();

if (isset($response->error_code)) {
    switch($response->error_code) {
        case "CUSTOMER_ALREADY_EXISTS":
            // do I need to handle anything here?
            break;
    }
}
p_2(SU_MERCHANT_CODE);
try {
    $checkout = $sumup->checkouts()->create(new \SumUp\Types\CheckoutCreateRequest(
        checkoutReference: $order_details['order_id'],
        amount: $order_details['totals']['total'],
        currency: 'GBP',
        merchantCode: SU_MERCHANT_CODE,
        description: "Subscription",
        purpose: 'SETUP_RECURRING_PAYMENT',
        customerId: $order_details['customer_su_id']
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

dd($checkout);

// $response = $checkout->createCheckout(true)->getResponse();

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
            $query = "SELECT su_checkout_id FROM Subscriptions WHERE subscription_id = ?";
            $result = $mem_db->query($query, [$order_details['order_id']])->fetch();
            $checkout->setCheckoutId($result['su_checkout_id']);
            $response = $checkout->retrieveCheckout()->getResponse();
            break;
    }
} else {
    $query = "UPDATE Subscriptions SET su_checkout_id = ? WHERE subscription_id = ?";
    try {
        $mem_db->query($query, [$response->id, $order_details['order_id']]);
    } catch (Exception $e) {
        error_log($e);
        exit("There has been an error. Please contact the webmaster.");
    }
}

p_2($order_details);

echo $m->render("members/payment_form", ["order_details"=>$order_details, "payment_message"=>$payment_message]);