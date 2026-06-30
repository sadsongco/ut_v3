<?php

require_once(__DIR__ . "/../../functions.php");

if (isset($_GET['form']) && !isset($_GET['subscription-level'])) {
    error_log("functions/interface/members/get_sub_price.php: invoked without subscription level");
    exit("You've arrived here from the wrong place");
}

function getSubPrice($sub_level) {
    switch ($sub_level) {
        case 'STANDARD':
            return round(STANDARD_SUB_PRICE + (STANDARD_SUB_PRICE * (SU_COM_RATE_PC / 100)) + (STANDARD_SUB_PRICE * (VAT_RATE_PC / 100)), 4);
            break;
        case 'PREMIUM':
            return round(PREMIUM_SUB_PRICE + (PREMIUM_SUB_PRICE * (SU_COM_RATE_PC / 100)) + (PREMIUM_SUB_PRICE * (VAT_RATE_PC / 100)), 4);
            break;
        default:
            error_log("functions/interface/members/get_sub_price.php: invalid subscription level");
            throw new Exception ("Invalid subscription level");
            break;
    }
}

if (isset($_GET['form'])) {
    try {
        $sub_price = getSubPrice($_GET['subscription-level']);
        echo number_format($sub_price, 2);
    }
    catch (Exception $e) {
        error_log($e);
        exit("Invalid subscription level");
    }
}