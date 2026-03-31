<?php

if(session_status() === PHP_SESSION_NONE) session_start();

include_once(__DIR__ . "/../../functions.php");
include_once(base_path("functions/shop/get_cart_items.php"));
include_once(base_path("classes/Database.php"));
use Database\Database;

class PackagingCosts {
    public const LABOUR = 0.5;
    public const PACKAGING = 1;
}

function calculateShipping($db, $zone, $method) {
    try {
        $query = "SELECT package_id, name
        FROM Packages
        WHERE max_length_mm >= ?
        AND max_width_mm >= ?
        AND max_depth_mm >= ?
        AND max_weight_g >= ?
        AND zone = ?";
        $package_zone = $zone == "UK" ? "UK" : "ROW";
        $params = [
            $_SESSION['package_specs']['length'],
            $_SESSION['package_specs']['width'],
            $_SESSION['package_specs']['depth'],
            $_SESSION['package_specs']['weight'],
            $package_zone
        ];
        $package_specs = $db->query($query, $params)->fetch();
        $_SESSION['package_specs']['package_id'] = $package_specs['package_id'];
        $_SESSION['package_specs']['package_name'] = $package_specs['name'];
        $query = "SELECT
            shipping_price
        FROM Shipping_prices
        WHERE package_id = ?
        AND shipping_method_id = ?
        AND rm_zone = ?
        AND min_weight_g <= ?
        AND max_weight_g >= ?";
        $params = [
            $package_specs['package_id'],
            $method['shipping_method_id'],
            $zone,
            $_SESSION['package_specs']['weight'],
            $_SESSION['package_specs']['weight']
        ];
        $shipping_price = $db->query($query, $params)->fetch();
        if (!$shipping_price) throw new Exception("No applicable shipping price found");
        return [
            $shipping_price['shipping_price'] + $_SESSION['package_specs']['package_price'] + PackagingCosts::LABOUR,
            $package_specs['package_id'],
            $package_specs['name']
        ];
    } catch (Exception $e) {
        error_log($query);
        error_log(print_r($params, true));
        throw new Exception($e);
    }
    return 0;
}


if (isset($_POST['update'])) {

    // p_2($_POST);
    $db = new Database('orders');
    $shipping_method = $db->query("SELECT * FROM Shipping_methods WHERE shipping_method_id = ?", [$_SESSION['shipping_method']['shipping_method_id']])->fetch();
    $shipping = 0;
    if (!isset($_SESSION['package_specs']['e_delivery'])) {
        [$shipping, $package_id, $package_name] = calculateShipping($db, $_SESSION['rm_zone'], $shipping_method);
        $_SESSION['shipping'] = round($shipping, 2);
    }

    $tariff = getTariffCosts($_POST['delivery-country'], $db);

    $shipping += $tariff;

    header("HX-Trigger: shippingUpdated");
    echo number_format($shipping, 2);
    if ($tariff) {
        echo "<div id='tariff' class='tariffMessage' hx-swap-oob='true'>This includes tariff costs of &pound;" . number_format($tariff, 2) . "</div>";
    } else {
        echo "<div id='tariff' hx-swap-oob='true'></div>";
    }
}


function getTariffCosts($country_code, $db) {
    $tariff = false;
    $query = "SELECT * FROM Tariffs WHERE country_id = ?";
    $params = [$country_code];
    $tariff_rates = $db->query($query, $params)->fetchAll();
    foreach ($tariff_rates AS $tariff_rate) {
        if ($tariff_rate['tariff_type'] === 'flat' && !$tariff_rate['per_hs_code'] && !$tariff_rate['per_item']) {
            $tariff += $tariff_rate['flat_currency'] === 'GBP' ? $tariff_rate['tariff_amount'] : convertCurrency($tariff_rate['tariff_amount'], $tariff_rate['flat_currency'], $db);
            // echo "adding tariff flat rate of " . $tariff_rate['tariff_amount'] . "<br>";
            continue;
            }
        if ($tariff_rate['tariff_type'] === 'flat' && $tariff_rate['per_item']) {
            $no_items = calculateNoItems();
            $tariff += $tariff_rate['flat_currency'] === 'GBP' ? $tariff_rate['tariff_amount'] * $no_items : convertCurrency($tariff_rate['tariff_amount'] * $no_items, $tariff_rate['flat_currency'], $db);
            continue;
        }
        if ($tariff_rate['tariff_type'] === 'flat' && $tariff_rate['per_hs_code']) {
            $no_hs_codes = calculateNoHsCodes($db);
            $tariff += $tariff_rate['flat_currency'] === 'GBP' ? $tariff_rate['tariff_amount'] * $no_hs_codes : convertCurrency($tariff_rate['tariff_amount'] * $no_hs_codes, $tariff_rate['flat_currency'], $db);
        }
        if ($tariff_rate['tariff_type'] === 'pc') {
            $cost_of_tariffable_items = calculateTariffItemCost($tariff_rate['customs_description'], $db);
            $tariff += $cost_of_tariffable_items * $tariff_rate['tariff_amount'] / 100;
        }
    }
    return $tariff;
}

/**
 * Calculate the total number of physical items in the cart.
 *
 * @return int The total number of items in the cart.
 */
function calculateNoItems() {
    $no_items = 0;
    if (isset($_SESSION['items'])) {
        foreach ($_SESSION['items'] as $item) {
            if ($item['e_delivery']) continue;
            $no_items += $item['quantity'];
        }
    }
    if (isset($_SESSION['bundles'])) {
        foreach ($_SESSION['bundles'] as $bundle) {
            foreach($bundle['items'] as $item) {
                if ($item['e_delivery']) continue;
                $no_items += $item['quantity'] * $bundle['quantity'];
            }
        }
    }
    return $no_items;
}

function calculateNoHSCodes($db) {
    $hs_codes = [];
    if (isset($_SESSION['items'])) {
        $items = getCartItems($_SESSION['items'], $db);
        foreach ($items as $item) {
            if ($item['e_delivery']) continue;
            if ($item['add_to_order']) continue;
            $hs_codes[] = $item['customs_description'];
        }
    }
    if (isset($_SESSION['bundles'])) {
        foreach ($_SESSION['bundles'] as $bundle) {
            $bundle_items = getCartItems($bundle['items'], $db);
            foreach($bundle_items as $item) {
                if ($item['e_delivery']) continue;
                if ($item['add_to_order']) continue;
                $hs_codes[] = $item['customs_description'];
            }
        }
    }
    return sizeof(array_unique($hs_codes));
    
}

function convertCurrency($amt, $currency, $db) {
    $query = "SELECT conversion_json FROM Currency_conversion WHERE next_update > " . time();
    $result = $db->query($query)->fetch();
    if (!$result) {
        $conversion_json = getCurrentCurrencyConversion($db);
    } else {
        $conversion_json = $result['conversion_json'];
    }
    $conversion = json_decode($conversion_json, true);
    $amt = $amt * $conversion[$currency];
    return $amt;
}

function calculateTariffItemCost($customs_description, $db) {
    $cost_of_tariffable_items = 0;
    if (isset($_SESSION['items'])) {
        foreach($_SESSION['items'] AS $item) {
            $query = "SELECT customs_description FROM Items WHERE item_id = ?";
            $item_details = $db->query($query, [$item['item_id']])->fetch();
            if ($item_details['customs_description'] === $customs_description) {
                $cost_of_tariffable_items += $item['price'] * $item['quantity'];
            }
        }
    }
    if (isset($_SESSION['bundles'])) {
        foreach ($_SESSION['bundles'] AS $bundle) {
            $bundle_items = getCartItems($bundle['items'], $db);
            foreach ($bundle_items AS $item) {
                $query = "SELECT customs_description, price FROM Items WHERE item_id = ?";
                $item_details = $db->query($query, [$item['item_id']])->fetch();
                if ($item_details['customs_description'] === $customs_description) {
                    $cost_of_tariffable_items += $item_details['price'] * $item['quantity'] * $bundle['quantity'];
                }
            }
        }
    }
    return $cost_of_tariffable_items;
}

function getCurrentCurrencyConversion($db) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, "https://open.er-api.com/v6/latest/GBP");
    $conversion_json = curl_exec($ch);
    // $test_json = '{"result":"success","provider":"https://www.exchangerate-api.com","documentation":"https://www.exchangerate-api.com/docs/free","terms_of_use":"https://www.exchangerate-api.com/terms","time_last_update_unix":1772409752,"time_last_update_utc":"Mon, 02 Mar 2026 00:02:32 +0000","time_next_update_unix":1772497102,"time_next_update_utc":"Tue, 03 Mar 2026 00:18:22 +0000","time_eol_unix":0,"base_code":"GBP","rates":{"GBP":1,"AED":4.93196,"AFN":84.655328,"ALL":109.929459,"AMD":508.564799,"ANG":2.403869,"AOA":1247.736688,"ARS":1950.28963,"AUD":1.898054,"AWG":2.403869,"AZN":2.289882,"BAM":2.230419,"BBD":2.685887,"BDT":164.123084,"BGN":2.212159,"BHD":0.504947,"BIF":3999.825243,"BMD":1.342943,"BND":1.703296,"BOB":9.320099,"BRL":6.933223,"BSD":1.342943,"BTN":122.614588,"BWP":17.981228,"BYN":3.893666,"BZD":2.685887,"CAD":1.834187,"CDF":3074.492537,"CHF":1.032879,"CLF":0.029554,"CLP":1168.178688,"CNH":9.226762,"CNY":9.22658,"COP":5047.438657,"CRC":637.412995,"CUP":32.230643,"CVE":125.74567,"CZK":27.611506,"DJF":238.669253,"DKK":8.508514,"DOP":81.00987,"DZD":175.037252,"EGP":64.414168,"ERN":20.144152,"ETB":210.712649,"EUR":1.140396,"FJD":2.946753,"FKP":1,"FOK":8.507496,"GEL":3.606119,"GGP":1,"GHS":14.405355,"GIP":1,"GMD":99.976813,"GNF":11774.283276,"GTQ":10.335093,"GYD":282.179452,"HKD":10.512638,"HNL":35.673812,"HRK":8.592307,"HTG":176.892228,"HUF":429.80064,"IDR":22537.01484,"ILS":4.216884,"IMP":1,"INR":122.614792,"IQD":1768.16309,"IRR":1767941.176471,"ISK":163.641775,"JEP":1,"JMD":210.22041,"JOD":0.952147,"JPY":209.83219,"KES":173.771468,"KGS":117.955902,"KHR":5420.815789,"KID":1.898264,"KMF":561.037631,"KRW":1943.458256,"KWD":0.412258,"KYD":1.119119,"KZT":670.819196,"LAK":29054.678518,"LBP":120193.439058,"LKR":416.42655,"LRD":247.268535,"LSL":21.506244,"LYD":8.516952,"MAD":12.326145,"MDL":23.01895,"MGA":5651.559177,"MKD":70.4037,"MMK":2831.786569,"MNT":4750.922082,"MOP":10.83024,"MRU":54.009177,"MUR":62.394651,"MVR":20.824414,"MWK":2342.800111,"MXN":23.195807,"MYR":5.24415,"MZN":86.230901,"NAD":21.506244,"NGN":1826.603566,"NIO":49.572857,"NOK":12.784031,"NPR":196.183341,"NZD":2.250769,"OMR":0.516358,"PAB":1.342943,"PEN":4.519693,"PGK":5.837415,"PHP":77.702802,"PKR":375.363667,"PLN":4.813253,"PYG":8707.007179,"QAR":4.888314,"RON":5.814195,"RSD":134.013935,"RUB":104.163193,"RWF":1964.817838,"SAR":5.036038,"SBD":10.708505,"SCR":18.728471,"SDG":603.194729,"SEK":12.166643,"SGD":1.703297,"SHP":1,"SLE":32.875557,"SLL":32878.346554,"SOS":770.059813,"SRD":50.956339,"SSP":6172.413227,"STN":27.939681,"SYP":151.300479,"SZL":21.506244,"THB":41.874025,"TJS":12.658734,"TMT":4.718622,"TND":3.858998,"TOP":3.180851,"TRY":59.045137,"TTD":9.157225,"TVD":1.898264,"TWD":42.260749,"TZS":3446.207493,"UAH":58.123123,"UGX":4829.021199,"USD":1.342952,"UYU":51.67669,"UZS":16466.263528,"VES":564.02287,"VND":34880.074423,"VUV":159.138987,"WST":3.605417,"XAF":748.050174,"XCD":3.625947,"XCG":2.403869,"XDR":0.98178,"XOF":748.050174,"XPF":136.085633,"YER":321.156982,"ZAR":21.506549,"ZMW":25.368744,"ZWG":34.7786,"ZWL":34.7786}}';
    // $conversion_json = $test_json;
    $conversion = json_decode($conversion_json, true);
    $rates_json = json_encode($conversion['rates']);
    $query = "INSERT INTO Currency_conversion (conversion_json, retrieved, next_update) VALUES (?, NOW(), ?)";
    $db->query($query, [$rates_json, $conversion['time_next_update_unix']]);
    return $rates_json;    
}