<?php

require_once("includes/privateIncludes.php");

try {
    $query = "INSERT INTO tabs VALUES (0, ?);";
    $stmt = $db->prepare($query);
    $stmt->execute([strtolower($_POST['tab_name'])]);
}
catch (Exception $e) {
    die ("Error adding tab: ".$e->getMessage());
}

echo $m->render("addTabForm", ["message"=>"Tab ".strtolower($_POST['tab_name'])." added successfully"]);

require_once(__DIR__."/../../../secure/scripts/ut_disconnect.php");

?>