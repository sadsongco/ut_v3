<?php

include(__DIR__."/replace_tags.php");
include(__DIR__."/mailout_create.php");

/* *** FUNCTIONS *** */

function makeLogDir ($path) {
    return is_dir($path) || mkdir($path);
}

function write_to_log ($log_fp, $output) {
    fwrite($log_fp, $output);
    fclose($log_fp);
}

function delete_current_mailout($current_mailout_file) {
    $fp = fopen($current_mailout_file, 'w');
    fwrite($fp, '');
    fclose($fp);
}

function email_admin($mail, $msg) {
    $mail->Subject = 'Unbelievable Truth mailout admin email';
    $mail->msgHTML($msg);
    $mail->addAddress('info@unbelievabletruth.co.uk', 'Info');
    $mail->send();
}

function get_email_addresses($db, $mailout_id, $mailing_list_table, $log_fp) {
    if (ENV !== "production") $cond = ' AND email LIKE "%sadsongco%" ';
    try {
        if ($mailout_id == 'test') $mailout_id = 1;
        else $mailout_id = (int)$mailout_id;
        $query = "SELECT email, name, email_id
        FROM $mailing_list_table
        WHERE last_sent < ?
        AND subscribed = 1 $cond
        AND error = 0
        ORDER BY domain
        LIMIT 1";
        return $db->query($query, [$mailout_id])->fetchAll();
    }
    catch(PDOException $e) {
        global $mail;
        write_to_log($log_fp, "\nget_email_addresses Database Error: " . $e->getMessage());
        email_admin($mail, "<p>get_email_addresses Database Error: " . $e->getMessage()."</p>");
        exit();
    }
}

function mark_as_sent($db, $mailing_list_table, $mailout_id, $row) {
    if ($mailout_id == 'test') $mailout_id = 1;
    else $mailout_id = (int)$mailout_id;
    try {
        $query = "UPDATE $mailing_list_table SET last_sent = ? WHERE email_id = ? AND email = ?";
        $params = [$mailout_id, $row['email_id'], $row['email']];
        $db->query($query, $params);
        return "$mailout_id\tMessage sent\t".htmlspecialchars($row['email'])."\t".date("Y-m-d H:i:s");
    }
    catch(PDOException $e) {
        return  "mark_as_sent Database Error: " . $e->getMessage();
    }
}

function mark_as_error($db, $mailing_list_table, $mailout_id, $row) {
    if ($mailout_id == 'test') $mailout_id = 1;
    else $mailout_id = (int)$mailout_id;

    try {
        $stmt = $db->prepare("UPDATE `$mailing_list_table` SET `error` = 1 WHERE `email_id` = ? AND `email` = ?");
        $stmt->execute([$row['email_id'], $row['email']]);
        return "$mailout_id\t::ERROR SENDING::\t".$row['email'];}
    catch(PDOException $e) {
        return "mark_as_error Database Error: " . $e->getMessage();
    }

}

/* ************************** */

require_once(base_path("../secure/mailauth/ut.php"));
include(base_path("../secure/secure_id/secure_id_ut.php"));

//Import the PHPMailer class into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

error_reporting(E_ERROR | E_PARSE);

date_default_timezone_set('Europe/London');

require base_path('../lib/vendor/autoload.php');

// set up PHP Mailer
//Passing `true` enables PHPMailer exceptions
$mail = new PHPMailer(true);

// create log
makeLogDir($log_dir);
$log_fp = fopen("$log_dir$current_mailout.txt", 'a');

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

// set up emails
try {
    $mailout_data = getMailoutData($current_mailout_id, $db);
}
catch (Exception $e) {
    write_to_log($log_fp, "\nFATAL: missing email body file: ".$e->getMessage());
    delete_current_mailout($current_mailout_file);
    email_admin($mail, "FATAL: missing email body file: ".$e->getMessage()." - messages stopped");
    exit();
}

$result = get_email_addresses($db, $current_mailout, $mailing_list_table, $log_fp);

if (sizeof($result) == 0) {
    write_to_log($log_fp, "\n\n--------COMPLETE--------");
    delete_current_mailout($current_mailout_file);
    email_admin($mail, "<h2>ALL EMAILS SENT. Check " . MAILOUT_LOG_PATH . $current_mailout . ".txt for details<h2>");
    exit();
}

$output = "";

include_once(__DIR__."/generate_mailout_content.php");
include_once(__DIR__."/generate_mailout_email_content.php");
$replacements = generateMailoutContent($mailout_data, $m, $db);
$replacements['host'] = getHost();
$replacements['remove_path'] = $remove_path;

$mail->Subject = $replacements["subject"];


foreach ($result as $row) {
    try {
        $bodies = generateMailoutEmailContent($replacements, $row, $m);
        $mail->msgHTML($bodies["html_body"]);
        $mail->AltBody = $bodies["text_body"];
        $mail->addAddress($row['email'], $row['name']);
    } catch (Exception $e) {
        $output .= "\n".mark_as_error($db, $mailing_list_table, $current_mailout, $row);
        $output .=  "\nInvalid address ".$row['email']." skipped";
        $output .= "\nREMOVE: " . replaceTags($remove_path, $row);
        continue;
    }
    
    try {
        $mail->send();
        //Mark it as sent in the DB
        $output .=  "\n".mark_as_sent($db, $mailing_list_table, $current_mailout, $row);
    } catch (Exception $e) {
        $output .= "\n".mark_as_error($db, $mailing_list_table, $current_mailout, $row);
        $output .= "\nPHPMailer Error :: ".$mail->ErrorInfo;
        $output .= "\nREMOVE: " . replaceTags($remove_path, $row);
        //Reset the connection to abort sending this message
        //The loop will continue trying to send to the rest of the list
        echo ($output);
        $mail->getSMTPInstance()->reset();
    }
    //Clear all addresses and attachments for the next iteration
    $mail->clearAddresses();
    $mail->clearAttachments();
}

// create log
write_to_log($log_fp, $output);