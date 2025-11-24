<?php

include(__DIR__ . "/../../functions.php");
include("includes/content_includes.php");

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// local functions
function sendResetEmail ($email, $selector, $token, $m, $mail_auth) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') $protocol .= 's';
    $host = "$protocol://".$_SERVER['HTTP_HOST'];
    $email_html = $m->render('resetPWEmail', ["host"=>$host, "selector"=>urlencode($selector), "token"=>urlencode($token)]);
    $email_txt = $m->render('resetPWEmailTxt', ["host"=>$host, "selector"=>urlencode($selector), "token"=>urlencode($token)]);
    $subject = "Unbelievable Truth - password reset request";

    // set up PHP Mailer
    //Passing `true` enables PHPMailer exceptions
    $mail = new PHPMailer(true);

    // setup email variables
    $mail->isSMTP();
    $mail->Host = $mail_auth['host'];
    $mail->SMTPAuth = true;
    $mail->SMTPKeepAlive = false; //SMTP connection will not close after each email sent, reduces SMTP overhead
    $mail->Port = 25;
    $mail->Username = $mail_auth['username'];
    $mail->Password = $mail_auth['password'];
    $mail->setFrom($mail_auth['from']['address'], "Unbelievable Truth - website registration");
    $mail->addReplyTo($mail_auth['reply']['address'], "Unbelievable Truth - website registration");
    $mail->addAddress($email);
    //Content
    $mail->isHTML(true);                                  //Set email format to HTML
    $mail->Subject = $subject;
    $mail->Body    = $email_html;
    $mail->AltBody = $email_txt;

    $mail->send();
}

$host = getHost();

try {
    $auth->forgotPassword($_POST['email'], function ($selector, $token) use ($m) {
        require_once(base_path("../secure/mailauth/ut.php"));
        try {
            sendResetEmail($_POST['email'], $selector, $token, $m, $mail_auth);
            echo "<p>Confirmation email sent to ".$_POST['email']."</p>";
        }
        catch (Exception $e) {
            echo "Couldn't send confirmation email: ";
            echo $e->getMessage();
        }
    });

}
catch (\Delight\Auth\InvalidEmailException $e) {
    die('Invalid email address');
}
catch (\Delight\Auth\EmailNotVerifiedException $e) {
    die('Email not verified');
}
catch (\Delight\Auth\ResetDisabledException $e) {
    die('Password reset is disabled');
}
catch (\Delight\Auth\TooManyRequestsException $e) {
    die('Too many requests');
}

?>