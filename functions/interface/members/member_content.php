<?php

require_once(__DIR__ . "/../../functions.php");

use Database\Database;

$member_db = new Database('members');

function makeUniqueToken($auth, $track) {
    return hash('sha1', $auth->getUsername().$track["content_filename"]);
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
        exit("NOT SUBSCRIBED");
    }
}
catch (Exception $e) {
    exit("DATABASE ERROR: " . $e->getMessage());
}

$query = "SELECT * FROM Scheduled_Content WHERE content_pub_date < NOW() ORDER BY content_pub_date ASC";

try {
    $scheduled_content = $member_db->query($query)->fetchAll();
}
catch (Exception $e) {
    exit("DATABASE ERROR: " . $e->getMessage());
}

foreach ($scheduled_content as &$content) {
    $content[$content['content_type']] = true;
    if ($content['content_type'] == 'Audio') {
        $content['token'] = makeUniqueToken($auth, $content);
        $track = [
            "id" => $content['scheduled_content_id'],
            "filename" => $content['content_filename'],
            "title" => $content['content_heading'],
            "notes" => $content['content_body'],
            "token" => $content['token'],
            "members" => true
        ];
        $content['track'] = json_encode($track);
    }
}


echo $m->render('members/member_content', ["scheduled_content"=>$scheduled_content]);