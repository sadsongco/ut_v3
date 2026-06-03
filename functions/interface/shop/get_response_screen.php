<?php

include("../../functions.php");
require(base_path("classes/Database.php"));

use Database\Database;
$db = new Database('orders');

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'failed') {
        echo $m->render('shop/checkout_failed');
        exit();
    }
    if ($_GET['status'] == 'timeout') {
        echo $m->render('shop/checkout_timeout');
        exit();
    }
}