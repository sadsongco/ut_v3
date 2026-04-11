<?php

if(session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['order_id'])) exit(); // no order id, nothing we can do here

if (!defined('ENV')) include_once(__DIR__ ."/../../../functions/functions.php");

//Load Composer's autoloader
require base_path('../lib/vendor/autoload.php');
//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function email_admin($mail) {
    try {
        $subject = 'Unbelievable Truth - confirm your email';
        require_once(base_path("../secure/mailauth/ut.php"));

        // mail auth
        $mail->isSMTP();
        $mail->Host = $mail_auth['host'];
        $mail->SMTPAuth = true;
        $mail->SMTPKeepAlive = false; //SMTP connection will not close after each email sent, reduces SMTP overhead
        $mail->Port = 25;
        $mail->Username = $mail_auth['username'];
        $mail->Password = $mail_auth['password'];
        $mail->Subject = 'Unbelievable Truth - order failed, payment taken';
        $mail->setFrom($mail_auth['from']['address'], $mail_auth['from']['name']);
        $mail->msgHTML("Order failed, payment taken. Check log for details.");
        $mail->addAddress('info@unbelievabletruth.co.uk', 'Info');
        $mail->send();
    } catch (Exception $e) {
        error_log($e);
        error_log("Message could not be sent. Mailer Error: " . $mail->ErrorInfo);
    }
}

require_once(base_path("classes/Database.php"));
use Database\Database;

if (!isset($db)) $db = new Database('orders');

$order_id = explode("-",$_SESSION['order_id'])[1];


if (isset($_POST['status']) && $_POST['status'] == 'FAILED') {
    try {
        $query = "SELECT
                Customers.name AS customer_name,
                Customers.address_1,
                Customers.address_2,
                Customers.city,
                Customers.postcode,
                Countries.name AS country,
                Customers.email,
                New_Orders.subtotal,
                New_Orders.shipping,
                New_Orders.vat,
                New_Orders.total,
                New_Orders.transaction_id
            FROM New_Orders
            JOIN Customers ON New_Orders.customer_id = Customers.customer_id
            JOIN Countries ON Customers.country = Countries.country_id
            WHERE New_Orders.order_id = ?";
        $db->beginTransaction();
        $order = $db->query($query, [$order_id])->fetch();
        $db->commit();
        $error_message = "Order " . $_SESSION['order_id'] . " FAILED";
        if (isset($_POST['reason'])) $error_message .= " for reason " . $_POST['reason'] . "\nOrder details:";
        $error_message .= "\n\n" . json_encode($_SESSION, JSON_PRETTY_PRINT);
        $error_message .= "\n\n" . json_encode($order, JSON_PRETTY_PRINT);
        if ($order['transaction_id']) {
            $error_message .= "\n\nPAYMENT TAKEN!";
            $mail = new PHPMailer(true);
            email_admin($mail);
        }
        $error_message .= "\n---------*********---------\n\n\n";
        error_log($error_message);
        if ($order['transaction_id']) exit();
        $db->beginTransaction();
        $query = "DELETE FROM New_Orders WHERE order_id = ?";
        $db->query($query, [$order_id]);
        if (isset($_SESSION['items'])) {
            foreach($_SESSION['items'] AS $item) {
                returnStock($item, $db);
            }
        }
        if (isset($_SESSION['bundles'])) {
            foreach($_SESSION['bundles'] AS $bundle) {
                foreach($bundle['items'] AS $item) {
                    $item['quantity'] = $bundle['quantity'];
                    returnStock($item, $db);
                }
            }
        }
        $db->commit();
        $response = [
            'status' => 'failed',
            'response'=> $_POST
        ];
        unset($_SESSION['order_id']);
        echo json_encode($response);
        exit();
    }
    catch (Exception $e) {
        $db->rollback();
        error_log($e);
        exit();
    }
}


$query = "UPDATE New_Orders SET transaction_id = ? WHERE order_id = ?";
try {
    $db->query($query, [$_POST['transaction_id'], $order_id]);
}
catch (Exception $e) {
    error_log($e);
}

$response = [
    'status' => 'success',
    'response'=> $_POST
];

echo json_encode($response);

function returnStock($item, $db)
{
    if (isset($item['option_id']) && $item['option_id']) {
        $query = "UPDATE Item_options SET option_stock = option_stock + ? WHERE item_option_id = ?";
        $params = [$item['quantity'], $item['option_id']];
        $db->query($query, $params);
    } else {
        $query = "UPDATE Items SET stock = stock + ? WHERE item_id = ?";
        $params = [$item['quantity'], $item['item_id']];
        $db->query($query, $params);
    }
}