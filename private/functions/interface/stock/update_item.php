<?php

require(__DIR__ . "/../../../../functions/functions.php");
require(base_path("classes/Database.php"));

use Database\Database;
$db = new Database('orders');

if (isset($_POST['update_item'])) {
    if (isset($_POST['featured'])) $_POST['featured'] = 1;
    else $_POST['featured'] = "0";
    if (isset($_POST['e_delivery'])) $_POST['e_delivery'] = 1;
    else $_POST['e_delivery'] = "0";
    unset ($_POST['update_item']);
    $_POST['release_date'] = $_POST['release_date'] ?? null;

    $query = "UPDATE Items SET ";
    [$query, $params] = buildUpdateQuery($query, "item_id");
    $db->query($query, $params);
}

if (isset($_POST['update_option'])) {
    unset ($_POST['update_option']);
    $query = "UPDATE Item_options SET ";
    [$query, $params] = buildUpdateQuery($query, "item_option_id");
    $db->query($query, $params);
}

if (isset($_POST['add_new_option'])) {
    unset($_POST['add_new_option']);
    $query = "INSERT INTO Item_options ";
    $fields = [];
    $insert = [];
    $params = [];
    foreach ($_POST as $field=>$value) {
        if (!$value) $value = NULL;
        $fields[] = $field;
        $insert[] = "?";
        $params[] = $value;
    }
    
    $query .= "(" . implode(", ", $fields) . ") VALUES (" . implode(", ", $insert) . ")";
    $db->query($query, $params);
}

if (isset($_POST['delete_item'])) {
    $query = "SELECT image FROM Items WHERE item_id = ?";
    $image = $db->query($query, [$_POST['item_id']])->fetch();
    unlink(base_path("assets/images/shop/item_images/" . $image['image']));
    $query = "DELETE FROM Items WHERE item_id = ?";
    $db->query($query, [$_POST['item_id']]);
}

echo "<h2>Item Updated</h2>";
echo "<script>
let el" . $_POST['item_id'] . " = document.getElementById('item" . $_POST['item_id'] . "')
let messageEl" . $_POST['item_id'] . " = document.getElementById('item" . $_POST['item_id'] . "-message');
let target" . $_POST['item_id'] . " = document.getElementById('item" . $_POST['item_id'] . "-content');
el" . $_POST['item_id'] . ".classList.toggle('is-open');
target" . $_POST['item_id'] . ".style.transition = 'max-height 0.5s ease-in-out, padding 0.5s ease-in-out';
target" . $_POST['item_id'] . ".style.padding = '0px';
target" . $_POST['item_id'] . ".style.maxHeight = '0px';
el" . $_POST['item_id'] . ".querySelector('i').classList.replace('fa-minus', 'fa-plus');
messageEl" . $_POST['item_id'] . ".scrollIntoView({ behavior: 'smooth', block: 'start' });
</script>";

function buildUpdateQuery($query, $index) {
    $update = [];
    $params = [];
    foreach ($_POST as $field=>$value) {
        if ($field == $index) continue;
        if (!$value) $value = NULL;
        $params[$field] = $value;
        if ($field == 'item_id') continue;
        $update[] = "$field = :$field";
    }
    $query .= implode(", ", $update);
    $query .= " WHERE $index = :$index";
    $params[$index] = $_POST[$index];
    return [$query, $params];
}