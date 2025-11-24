<?php

include(__DIR__ . "/../../../../functions/functions.php");

require(base_path("classes/Database.php"));
require (base_path("/functions/utility/decrypt_token.php"));
require (base_path("/functions/utility/trigger_download.php"));
require (base_path("/functions/utility/create_order_pdf.php"));
require (base_path('functions/shop/make_order_pdf.php'));

use Database\Database;
$db = new Database('orders');

$order_id = $_GET['order_id'];

$filename = createOrderPDF($order_id, $db);

$file_path = base_path(ORDER_PDF_PATH . $filename);

triggerDownload($filename, $file_path);

unlink($file_path);