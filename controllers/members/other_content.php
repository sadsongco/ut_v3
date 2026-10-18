<?php
require_once(__DIR__ . "/../../functions/functions.php");
include(base_path('classes/ArticleRenderer.php'));
global $auth;
global $m;

use Database\Database;
$member_db = new Database('members');

function makeUniqueToken($auth, $track) {
    return hash('sha1', $auth->getUsername().$track["filename"]);
}

if (!$auth->isLoggedIn()) {
    exit("NOT LOGGED IN");
}

$today = date('Y-m-d') . " 23:59:59";
$period_start = date('Y-m-d', strtotime('-1 month', strtotime($today))) . " 00:00:00";

$query = "SELECT Subscriptions.user_id
    FROM Subscriptions
    JOIN Subscription_Transactions
        ON Subscriptions.subscription_id = Subscription_Transactions.subscription_id
        AND Subscription_Transactions.transaction_date BETWEEN ? AND ?
    WHERE Subscriptions.user_id = ?";

$params = [$period_start, $today, $auth->getUserId()];

try {
    $result = $member_db->query($query, $params)->fetchAll();
    if (sizeof($result) == 0) {
        echo $m->render("members/manage_subscription", ["env"=>ENV]);
        exit("NOT SUBSCRIBED");
    }
}
catch (Exception $e) {
    exit("DATABASE ERROR: " . $e->getMessage());
}

if (isset($paths[2]) && $paths[2] != "") {
    $sc_id = $paths[2];
} else {
    $sc_id = $member_db->query("SELECT scheduled_content_id FROM Scheduled_Content WHERE pub_date < NOW() ORDER BY pub_date DESC LIMIT 1")->fetch()['scheduled_content_id'];
}

try {
    $content_data = $member_db->query("SELECT scheduled_content_id
        FROM Scheduled_Content
        WHERE scheduled_content_id != ?
        AND pub_date < NOW()
        ORDER BY pub_date DESC", [$sc_id])->fetchAll();
} catch (PDOException $e) {
    error_log($e);
    exit("DATABASE ERROR: " . $e->getMessage());
}

echo $m->render("members/other_content", ["other_content"=>$content_data]);