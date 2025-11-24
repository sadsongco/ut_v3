<?php


include(__DIR__ . "/../../../../functions/functions.php");
include_once(base_path("../secure/secure_id/secure_id_ut.php"));
require_once(base_path("functions/email/email_includes.php"));

$bandcamp_list = [];
$fp = fopen(base_path('../web_assets/ut/data/bandcamp/bandcamp_mailing_list.csv'), 'r');
$keys = explode(",", trim(fgets($fp)));

while(!feof($fp)) {
    $row = explode(",", fgets($fp));
    if (sizeof($row) !== sizeof($keys)) continue;
    $bandcamp_list[] = array_combine($keys, $row);
}
ob_start();
foreach ($bandcamp_list AS $row) {
    echo "<p>";
    $query = "INSERT INTO ut_mailing_list (email, domain, name, date_added) VALUES (?, ?, ?, ?)";
    $date_added = new DateTime($row['date added']);
    $params = [
        $row['email'],
        explode("@", $row['email'])[1],
        $row['fullname'],
        $date_added->format("Y-m-d H:i:s")
    ];
    try {
        $db->query($query, $params);
        echo $row['email'] . " ADDED.\t\t&nbsp;&nbsp;&nbsp;";
        $insert_id = $db->lastInsertId();
    } catch (PDOException $e) {
        if ((int)$e->getCode() === 23000) {
            echo $row['email'] . " ALREADY EXISTS";
            ob_flush();
            continue;
        }
        else {
            error_log($e);
            echo "DATABASE ERROR: ";
            echo $e->getMessage();
            ob_flush();
            continue;
        }
    }

    $email_result = sendConfirmationEmail(['email'=>$row['email'], 'name'=>$row['fullname'], 'email_id'=>$insert_id], 'bandcamp');
    sleep(5);
    if ($email_result["success"]) {
        echo "Confirmation email sent. ";
    }
    echo "</p>";
    ob_end_flush();
    exit();
}
