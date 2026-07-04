<?php

require_once(__DIR__ . "/../../functions.php");

if (isset($_GET['form']) && !isset($_GET['subscription-level'])) {
    error_log("functions/interface/members/get_sub_price.php: invoked without subscription level");
    exit("You've arrived here from the wrong place");
}

function getSubPrice($sub_level) {
    switch ($sub_level) {
        case 'STANDARD':
            $sub_total = round(STANDARD_SUB_PRICE + (STANDARD_SUB_PRICE * (SU_COM_RATE_PC / 100)), 4);
            $vat = round($sub_total * (VAT_RATE_PC / 100), 4);
            $total = round($sub_total + $vat, 4);
            return [$sub_total, $vat, $total];
            break;
        case 'PREMIUM':
            $sub_total = round(PREMIUM_SUB_PRICE + (PREMIUM_SUB_PRICE * (SU_COM_RATE_PC / 100)), 4);
            $vat = round($sub_total * (VAT_RATE_PC / 100), 4);
            $total = round($sub_total + $vat, 4);
            return [$sub_total, $vat, $total];
            break;
        default:
            error_log("functions/interface/members/get_sub_price.php: invalid subscription level");
            throw new Exception ("Invalid subscription level");
            break;
    }
}

if (isset($_GET['form'])) {
    try {
        [$sub_total, $vat, $sub_price] = getSubPrice($_GET['subscription-level']);
        echo number_format($sub_price, 2);
    }
    catch (Exception $e) {
        error_log($e);
        exit("Invalid subscription level");
    }
}