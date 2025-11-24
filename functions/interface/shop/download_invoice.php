<?php

include("../../functions.php");
require(base_path("classes/Database.php"));
require (base_path("/functions/utility/decrypt_token.php"));
require (base_path("/functions/utility/trigger_download.php"));
require (base_path("/functions/utility/create_order_pdf.php"));
require (base_path('functions/shop/make_order_pdf.php'));

use Database\Database;
$db = new Database('orders');

if (!isset($_GET['token'])) exit("No Token");
if (!isset($_GET['id'])) exit("No ID");

$customer_id = decryptUniqueToken($_GET['token']);
$order_id = $_GET['id'];

$query = "SELECT customer_id FROM New_Orders WHERE order_id = ?";
$result = $db->query($query, [$order_id])->fetch();
if ($result['customer_id'] != $customer_id) exit("Invalid Token");

$filename = createOrderPDF($order_id, $db);

$file_path = base_path(ORDER_PDF_PATH . $filename);

triggerDownload($filename, $file_path);

unlink($file_path);