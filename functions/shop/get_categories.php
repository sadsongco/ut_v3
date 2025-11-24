<?php

function getCategories($db) {
    $query = "SELECT DISTINCT category FROM Items";
    $categories = $db->query($query)->fetchAll();
    foreach($categories as $key=>$category) {
        $query = "SELECT SUM(
            stock + (SELECT IFNULL(SUM(option_stock), 0) FROM Items JOIN Item_options ON Item_options.item_id = Items.item_id AND Items.category = ?)
            ) AS count FROM Items WHERE category = ?
        ";
        $count = (int)$db->query($query, [$category['category'], $category['category']])->fetch()['count'];
        if ($count === 0) unset($categories[$key]);
    }
    sort($categories);
    $categories[] = ["category"=>"All"];
    return $categories;
}