<?php

function getItems($db, $category=null)
{
    $where = "";
    $params = [];
    if ($category) {
        $where = "WHERE Items.category = ?";
        $params = [$category];
    }
    $query = "SELECT * FROM (
        SELECT
            Items.item_id, Items.name, Items.price, Items.image, Items.featured,
            IFNULL(Items.stock, 0) + (SELECT IFNULL(SUM(Item_options.option_stock), 0) FROM Item_options WHERE Item_options.item_id = Items.item_id) AS stock
            FROM Items
            $where
            GROUP BY Items.item_id
            ORDER BY featured DESC
        )T
    WHERE T.stock > 0";
    $items = $db->query($query, $params)->fetchAll();
    foreach ($items as &$item) {
        $item_options = $db->query("SELECT * FROM Item_options WHERE item_id = ? AND option_stock > 0", [$item['item_id']])->fetchAll();
        $item['option'] = sizeof($item_options) > 0 ? ['options'=>$item_options] : false;
        if (isset($item['image']) && $item['image'])
            $item['image_path'] = "/serve/" . SHOP_ASSET_PATH . "images/" . str_replace(".", "/", $item['image']);
    }
    return $items;
}