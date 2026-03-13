<?php

include(__DIR__ . "/../../../../functions/functions.php");
include(base_path("classes/SUCheckout.php"));
include(base_path("classes/Database.php"));

use Database\Database;
$db = new Database('orders');

use SUCheckout\SUCheckout;
$checkout = new SUCheckout();

$checkout->deleteTransaction($_GET['transaction_id']);
$result = $checkout->getResponse();
if (isset($result->error_code)) {
    switch($result->error_code) {
        case "NOT_FOUND":
            $result->db_message = "Transaction not found";
            break;
    }
}
if (isset($result->status)) {
    if ($result->status == "DELETED") {
        $query = "UPDATE Transactions SET transaction_id = NULL WHERE transaction_id = ?";
        $stmt = $db->query($query, [$_GET['transaction_id']]);
        $rows = $db->rowCount($stmt);
        if ($rows == 0) {
            $result->db_message = "Couldn't find order to update in database";
        } else {
            $result->db_message = "Updated order in database";
        }
    }
}

header("HX-Trigger: transactionListUpdated");
echo '<div id="updatedResult" hx-swap-oob="true">
<h2>DELETE TRANSACTION:</h2>
<p>'. print_r($result->db_message, true) . '</p>
</div>';