<?php

include(__DIR__ . "/../../../../functions/functions.php");
include(base_path("classes/Database.php"));
include(base_path("../lib/mustache.php-main/src/Mustache/Autoloader.php"));
include(__DIR__ . "/includes/make_income_pdf.php");

use Database\Database;
$db = new Database('orders');

Mustache_Autoloader::register();

$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/utility')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/utility/partials'))
));

if (!isset($_POST['period'])) exit("No tax period selected");
$period_arr = explode("::", $_POST['period']);
$period = [
    "start" => $period_arr[0],
    "end" => $period_arr[1]
];
$new_income = calculateNewIncome($db, $period);
$new_income_non_vat = calculateNewIncome($db, $period, false);
$old_income = calculateOldIncome($db, $period);
$old_income_non_vat = calculateOldIncome($db, $period, false);

$income = [
    "period" => $period['start'] . " - " . ($period['end']),
    "subtotal" => number_format($new_income['subtotal'] + $old_income['subtotal'], 2),
    "shipping" => number_format($new_income['shipping'] + $old_income['shipping'], 2),
    "gross" => number_format($new_income['subtotal'] + $new_income['shipping'] - $new_income['vat'] + $old_income['subtotal'] + $old_income['shipping'] - $old_income['vat'], 2),
    "vat_exempt_subtotal" => number_format($new_income_non_vat['subtotal'] + $old_income_non_vat['subtotal'], 2),
    "vat_exempt_shipping" => number_format($new_income_non_vat['shipping'] + $old_income_non_vat['shipping'], 2),
    "vat" => number_format($new_income['vat'] + $old_income['vat'], 2),
    "total" => number_format($new_income['total'] + $old_income['total'] + $new_income_non_vat['total'] + $old_income_non_vat['total'], 2)
];

$income_pdf_filename = makeIncomePDF($income, 'F', base_path(INCOME_PDF_PATH));

echo $m->render('income', ["income"=>$income, "period"=>$period, "save_path"=>INCOME_PDF_PATH, "filename"=>$income_pdf_filename, "host"=>getHost()]);

function calculateNewIncome($db, $period, $inc_vat = true) {
    $area_cond = $inc_vat ? " AND country_id = 31 OR country_id = 215" : " AND country_id != 31 AND country_id != 215";
    try {
        $query = "SELECT
            SUM(subtotal) AS subtotal,
            SUM(shipping) AS shipping,
            SUM(vat) AS vat,
            SUM(total) AS total
        FROM New_Orders
        JOIN Customers ON New_Orders.customer_id = Customers.customer_id
        JOIN Countries ON Customers.country = Countries.country_id
        WHERE order_date BETWEEN ? AND ?
        $area_cond;";
        $params = [$period['start'], $period['end']];
        $result = $db->query($query, $params)->fetch();
        return $result;
    } catch (PDOException $e) {
        error_log($e);
        return 0;
    }
}

function calculateOldIncome($db, $period, $inc_vat = true) {
    $area_cond = $inc_vat ? " AND country_id = 31 OR country_id = 215" : " AND country_id != 31 AND country_id != 215";
    try {
        $query = "SELECT
            SUM(subtotal) AS subtotal,
            SUM(shipping) AS shipping,
            SUM(vat) AS vat,
            SUM(total) AS total
        FROM Orders
        JOIN Customers ON Orders.customer_id = Customers.customer_id
        JOIN Countries ON Customers.country = Countries.country_id
        WHERE order_date BETWEEN ? AND ?
        $area_cond;";
        $params = [$period['start'], $period['end']];
        $result = $db->query($query, $params)->fetch();
        return $result;
    } catch (PDOException $e) {
        error_log($e);
        return 0;
    }
}