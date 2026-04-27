<?php

include(__DIR__."/replace_tags.php");
include(__DIR__."/mailout_create.php");

define("DELAY_MAILOUT_PATH", "mailout/data/delay_mailout.txt");

function generateMailoutEmailContent($replacements, $data, $m) {
    $secure_id = generateSecureId($data['email'], $data['email_id']);
    $replacements['name'] = $data['name'];
    $replacements['email'] = $data['email'];
    $replacements['secure_id'] = $secure_id;
    $text_body = $m->render("delayTextTemplate", $replacements);
    $html_body = $m->render("delayHtmlTemplate", $replacements);

    return [
        "text_body"=>$text_body,
        "html_body"=>$html_body,
        "subject"=>$replacements["subject"]
    ];        

}
/* *** FUNCTIONS *** */

function makeLogDir ($path) {
    return is_dir($path) || mkdir($path);
}

function write_to_log ($log_fp, $output) {
    fwrite($log_fp, $output);
    fclose($log_fp);
}

function email_admin($mail, $msg) {
    $mail->Subject = 'Unbelievable Truth mailout admin email';
    $mail->msgHTML($msg);
    $mail->addAddress('info@unbelievabletruth.co.uk', 'Info');
    $mail->send();
}

function get_artprint_email_addresses($db, $log_fp) {
    try {
        $query = "SELECT
            Customers.email, Customers.name, Customers.customer_id
        FROM
            Artprint_mailout
        JOIN
            Customers ON Artprint_mailout.customer_id = Customers.customer_id
        ORDER BY
            Artprint_mailout.sent_id ASC
        LIMIT
            1
        ";
        return $db->query($query)->fetch();

    }
    catch (PDOException $e) {
        global $mail;
        write_to_log($log_fp, "\nget_artprint_email_addresses Database Error: " . $e->getMessage());
        email_admin($mail, "<p>get_email_addresses Database Error: " . $e->getMessage()."</p>");
        exit();
    }
}

function remove_artprint_email_address($db, $row) {
    try {
        $query = "DELETE FROM Artprint_mailout WHERE customer_id = ?";
        $params = [$row['customer_id']];
        $db->query($query, $params);
        return "Artprint:\tMessage sent\t".htmlspecialchars($row['email'])."\t".date("Y-m-d H:i:s");
    }
    catch(PDOException $e) {
        return  "remove_artprint_email_addresses Database Error: " . $e->getMessage();
    }
}

/* ************************** */

$mailout_file = base_path(WEB_ASSET_PATH . DELAY_MAILOUT_PATH);

if (!file_exists($mailout_file)) exit();

require_once(base_path("../secure/mailauth/ut.php"));
include_once(base_path("../secure/secure_id/secure_id_ut.php"));

//Import the PHPMailer class into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

error_reporting(E_ERROR | E_PARSE);

date_default_timezone_set('Europe/London');

require base_path('../lib/vendor/autoload.php');

// set up PHP Mailer
//Passing `true` enables PHPMailer exceptions
$mail = new PHPMailer(true);

// create log directory
makeLogDir($log_dir);

// create log
$log_fp = fopen($log_dir . "delay_list.log", 'a');

// mail auth
$mail->isSMTP();
$mail->Host = $mail_auth['host'];
$mail->SMTPAuth = true;
$mail->SMTPKeepAlive = false; //SMTP connection will not close after each email sent, reduces SMTP overhead
$mail->Port = 25;
$mail->Username = $mail_auth['username'];
$mail->Password = $mail_auth['password'];
$mail->setFrom($mail_auth['from']['address'], $from_name);
$mail->addReplyTo($mail_auth['reply']['address'], $from_name);

// set up <emails></emails>
$row = get_artprint_email_addresses($db, $log_fp);

if (!$row) {
    write_to_log($log_fp, "\n\n--------COMPLETE--------");
    email_admin($mail, "<h2>ALL DELAYED 2LP EMAILS SENT. Check " . MAILOUT_LOG_PATH . "delay_list.log for details<h2>");
    unlink($mailout_file);
    exit();
}

$output = "";

$mail->Subject = "Almost Here vinyl album delayed (again)";

try {
    $bodies = generateMailoutEmailContent(["heading"=>"Almost Here vinyl album delayed (again)"], $row, $m);
    $mail->msgHTML($bodies["html_body"]);
    $mail->AltBody = $bodies["text_body"];
    $mail->addAddress($row['email'], $row['name']);
} catch (Exception $e) {
    $output .= "\n artprint email address " . $row['email'] . " failed";
    $output .=  "\nInvalid address ".$row['email']." skipped";
    $output .= "\nREMOVE: " . replaceTags($remove_path, $row);
}

try {
    $mail->send();
    //Mark it as sent in the DB
    $output .= "\n" . remove_artprint_email_address($db, $row);
} catch (Exception $e) {
    if (null !== DELAY) $output .= "\n artprint email address " . $row['email'] . " failed";
    else $output .= "\n".mark_as_error($db, $mailing_list_table, $current_mailout, $row);
    $output .= "\nPHPMailer Error :: ".$mail->ErrorInfo;
    $output .= "\nREMOVE: " . replaceTags($remove_path, $row);
    //Reset the connection to abort sending this message
    echo ($output);
    $mail->getSMTPInstance()->reset();
}
//Clear all addresses and attachments for the next iteration
$mail->clearAddresses();
$mail->clearAttachments();

// create log
write_to_log($log_fp, $output);
 