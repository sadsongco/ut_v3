<?php

include(__DIR__ . "/../../functions.php");
include("includes/content_includes.php");

function SendConfirmationEmail ($email, $selector, $token, $m, $mail_auth) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') $protocol .= 's';
    $host = "$protocol://".$_SERVER['HTTP_HOST'];
    $email_html = $m->render('authemails/confirmRegisterEmail', ["host"=>$host, "selector"=>$selector, "token"=>$token]);
    $email_txt = $m->render('authemails/confirmRegisterEmailTxt', ["host"=>$host, "selector"=>$selector, "token"=>$token]);
    $subject = "Unbelievable Truth - please confirm your email";

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

try {
    if (in_array($_POST['username'], RESERVED_USERNAMES)) throw new \Delight\Auth\UserAlreadyExistsException;
    $userId = $auth->register($_POST['email'], $_POST['password'], $_POST['username'], function ($selector, $token) {
        require_once(base_path("../secure/mailauth/ut.php"));
        try {
            SendConfirmationEmail($_POST['email'], $selector, $token, $m, $mail_auth);
            echo "<p>Confirmation email sent to ".$_POST['email']."</p>";
        }
        catch (Exception $e) {
            echo "Couldn't send confirmation email: ";
            echo $e->getMessage();
        }
    });
}

catch (\Delight\Auth\InvalidEmailException $e) {
    echo '<p class="error">Invalid email address</p>';
    echo $m->render('user_register', ["error"=>$e->getMessage()]);
    exit();
}
catch (\Delight\Auth\InvalidPasswordException $e) {
    echo '<p class="error">Invalid password</p>';
    echo $m->render('user_register', ["error"=>$e->getMessage()]);
    exit();
}
catch (\Delight\Auth\UserAlreadyExistsException $e) {
   echo "<p class='error'>That email or username is already registered!</p>";
   echo $m->render('user_register', ["error"=>$e->getMessage()]);
   exit();
}
catch (\Delight\Auth\TooManyRequestsException $e) {
    error_log('Too many requests: '.$e->getMessage());
    echo "There has been an error. Please try again later.";
    echo $e->getMessage();
}
