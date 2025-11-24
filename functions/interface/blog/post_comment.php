<?php
include(__DIR__ . "/../../functions.php");
require_once(__DIR__."/includes/content_includes.php");
include_once(base_path("../lib/vendor/autoload.php"));

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

use Database\Database;
$content_db = new Database('content');

function getCommentNotify($content_db, $reply) {
    try {
        $query = "SELECT notify, user_id FROM comments WHERE comment_id = ?;";
        $result = $content_db->query($query, [$reply])->fetchAll();
        return $result[0];
    }
    catch (Exception $e) {
        return 0;
    }
}

function sendNotification($content_db, $m, $user_id, $article_id) {
    $email = "info@unbelievabletruth.co.uk";
    if ($user_id != "admin") {
        try {
            $query = "SELECT email FROM users WHERE id = ?;";
            $result = $content_db->query($query, [$user_id])->fetchAll();
            $email = $result[0]['email'];
        } catch (Exception $e) {
            error_log($e);
            return;
        }
    }

    include(base_path("../secure/mailauth/ut.php"));
    $host = getHost();
    $email_html = $m->render('replyNotificationEmailHTML', ["host"=>$host, "article_id"=>$article_id]);
    $email_txt = $m->render('replyNotificationEmailTxt', ["host"=>$host, "article_id"=>$article_id]);
    $subject = "Unbelievable Truth - there's a reply to your comment";

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
    $mail->setFrom($mail_auth['from']['address'], "Unbelievable Truth - website");
    $mail->addReplyTo($mail_auth['reply']['address'], "Unbelievable Truth - website");
    $mail->addAddress($email);
    //Content
    $mail->isHTML(true);                                  //Set email format to HTML
    $mail->Subject = $subject;
    $mail->Body    = $email_html;
    $mail->AltBody = $email_txt;

    $mail->send();
}

function validateArticleId($article_id, $content_db) {
    $query = "SELECT COUNT(*) FROM articles WHERE article_id = ?;";
    $num_rows = $content_db->query($query, [$article_id])->fetchColumn();
    if ($num_rows > 0) return true;
    throw new Exception("invalid article id");
}

function validateCommentId($comment_id, $content_db) {
    $query = "SELECT EXISTS(SELECT 1 FROM comments WHERE comment_id = ? LIMIT 1)";
    $params = [$comment_id];
    return $content_db->query($query, $params)->fetchColumn();
}

$reply = null;
$notify = 0;

if (isset($_POST['notify'])) $notify = true; // doesn't need sanitisation? if it exists then it's true, otherwise false

if (isset($_POST['comment_reply_id']) && intval($_POST['comment_reply_id']) != 0) {
    if (!validateCommentId($_POST['comment_reply_id'], $content_db)) exit ('Invalid comment id');
    $reply = intval($_POST['comment_reply_id']);
    $email_notification = getCommentNotify($content_db, $reply);
    if ($email_notification['notify'] == 1) sendNotification($content_db, $m, $email_notification['user_id'], $_POST['article_id']);
}

sendNotification($content_db, $m, 'admin', $_POST['article_id']);


$params = [
    "user_id"=>$auth->getUserId(),
    "article_id"=>intval($_POST['article_id']),
    "reply"=>$reply,
    "reply_to"=>null,
    "notify"=>$notify,
    "comment"=>strip_tags($_POST['comment'])
];

try {
    validateArticleId($_POST['article_id'], $content_db);
    $query = "INSERT INTO comments VALUES (0, :user_id, :article_id, NOW(), :reply, :reply_to, 0, :notify, 0, :comment);";
    $stmt = $content_db->query($query, $params);
}
catch (Exception $e) {
    echo "Error inserting comment:";
    die($e->getMessage());
}

header ('HX-Trigger:refreshComments');
echo $m->render("comment_form_solo", ["article_id"=>$params['article_id'], "logged_in"=>$auth->isLoggedIn()]);
