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

$payment_details = [...$_POST];
[$payment_details['totals']['subtotal'], $payment_details['totals']['vat'], $payment_details['totals']['total']] = getSubPrice($payment_details['subscription_level']);
$payment_details['exp']['month'] = substr($payment_details['expiry_date'], 0, 2);
$payment_details['exp']['year'] = substr($payment_details['expiry_date'], -2, 2);

$payload = [
    'payment_type' => 'card',
    'mandate' => [
        "type" => "recurrent",
        "user_agent" => $_SERVER['HTTP_USER_AGENT'],
        "user_ip" => $_SERVER['REMOTE_ADDR']
    ],
    'card' => [
        "name" => $payment_details['name'],
        "number" => (int)str_replace(" ", "", $payment_details['number']),
        "expiry_year" => (int)$payment_details['exp']['year'],
        "expiry_month" => $payment_details['exp']['month'],
        "cvv" => (int)$payment_details['cvv'],
        "type" => $payment_details['type']
    ],
    'customer_id' => $payment_details['customer_id']
    ];
echo "<pre>";
var_dump($payload);
echo "</pre>";

try {
    $response = $sumup->checkouts()->process($payment_details['checkout_id'], $payload);
    p_2($response);
    if ($response->status === "FAILED") exit ("Transaction failed");

} catch (\SumUp\Exception\ApiException $e) {
    error_log($e);
    exit("There has been an error. Please contact the webmaster.");
} catch (\SumUp\Exception\UnexpectedApiException $e) {
    error_log($e);
    exit("There has been an error. Please contact the webmaster.");
} catch (\SumUp\Exception\SDKException $e) {
    error_log($e);
    exit("There has been an error. Please contact the webmaster.");
}