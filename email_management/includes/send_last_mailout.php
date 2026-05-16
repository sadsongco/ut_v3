<?php

include_once(base_path("../lib/vendor/autoload.php"));
//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

include_once(__DIR__ . '/get_latest_mailout.php');

function sendLastMailout($row, $last_sent, $db, $m) {
    
    if (!isset($row['name'])) $row['name'] = '';
    
    require(base_path("private/functions/interface/mailout/includes/mailout_create.php"));
    include_once(base_path("private/functions/interface/mailout/includes/generate_mailout_content.php"));
    include_once(base_path("private/functions/interface/mailout/includes/generate_mailout_email_content.php"));
    
    $last_mailout = getLatestMailout($db);
    if ($last_mailout['mailout_id'] == $last_sent) return ["success"=>true, "last_mailout"=>$last_mailout];
    if ($last_mailout == 0) throw new Exception("Test exception");
    $remove_path = '/email_management/unsubscribe.php';
    $last_mailout['subject'] = "[UNBELIEVABLE TRUTH]" . $last_mailout['subject'];
    //Create an instance; passing `true` enables exceptions
    $mail = new PHPMailer(true);
    
    try {
        $replacements = generateMailoutContent($last_mailout, $m, $db);
        $replacements['host'] = getHost();
        $replacements['remove_path'] = $remove_path;
        
        $mail->Subject = $replacements["subject"];
        $bodies = generateMailoutEmailContent($replacements, $row, $m);
        $mail->msgHTML($bodies["html_body"]);
        $mail->AltBody = $bodies["text_body"];

        require_once(base_path("../secure/mailauth/ut.php"));

        // mail auth
        $mail->isSMTP();
        $mail->Host = $mail_auth['host'];
        $mail->SMTPAuth = true;
        $mail->SMTPKeepAlive = false; //SMTP connection will not close after each email sent, reduces SMTP overhead
        $mail->Port = 25;
        $mail->Username = $mail_auth['username'];
        $mail->Password = $mail_auth['password'];
        $mail->setFrom($mail_auth['from']['address'], $mail_auth['from']['name']);
        $mail->addReplyTo($mail_auth['reply']['address'], $mail_auth['reply']['name']);
        //Recipients
        $mail->addAddress($row['email'], $row['name']);     //Add a recipient


        //Content
        $mail->isHTML(true);                                  //Set email format to HTML
        $mail->addAddress($row['email'], $row['name']);

        $mail->send();
        return ["success"=>true, "last_mailout"=>$last_mailout['mailout_id']];
    } catch (Exception $e) {
        error_log($mail->ErrorInfo);
        error_log($e);
        return ["success"=>false, "status"=>"email_error"];
    }
    return $last_mailout;
}