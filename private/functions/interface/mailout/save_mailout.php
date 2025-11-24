<?php

include_once("includes/mailout_includes.php");

$clear_create = '<section id="createMailout" hx-swap-oob="true"></section>';
if (isset($_POST['cancel'])) exit($clear_create);

try {
    $query = "INSERT INTO mailouts VALUES (NULL, NOW(), ?, ?, ?)";
    $params = [$_POST['subject'], $_POST['heading'], $_POST['body']];
    if (isset($_POST['edit'])) {
        $query = "UPDATE mailouts SET subject = ?, heading = ?, body = ? WHERE id = ?";
        $params[] = $_POST['id'];
    }
    $db->query($query, $params);
}
catch (PDOException $e) {
    exit("Couldn't save mailout: ".$e->getMessage());
}
header("HX-Trigger: listChange");
echo "<p>mailout saved</p>" . $clear_create;