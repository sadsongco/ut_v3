<?php

function getBundles($db, $category=null)
{
    $where = "";
    $params = [];
    if ($category) {
        $where = "
        JOIN `Bundle_items` ON Bundle_items.bundle_id = Bundles.bundle_id
        JOIN `Items` ON Bundle_items.item_id = Items.item_id
        AND Items.category = ?";
        $params = [$category];
    }
    $query = "SELECT
        Bundles.bundle_id,
        Bundles.price
    FROM `Bundles`
    $where
    WHERE `active` = 1
    GROUP BY Bundles.bundle_id";
    $bundles = $db->query($query, $params)->fetchAll();
    $return_bundles = [];
    foreach ($bundles as &$bundle) {
        $bundle['stock'] = 9999;
        $params = [$bundle['bundle_id']];
        $query = "SELECT
        Bundle_items.item_id,
        Items.item_id, Items.name, Items.price, Items.image, Items.featured,
        Items.stock + (SELECT IFNULL(SUM(Item_options.option_stock), 0) FROM Item_options WHERE Item_options.item_id = Items.item_id) AS stock
        FROM Bundle_items
        JOIN Items ON Bundle_items.item_id = Items.item_id
        WHERE Bundle_items.bundle_id = ?
        ";
        $bundle['items'] = $db->query($query, $params)->fetchAll();
        $bundle['raw_price'] = 0;
        foreach ($bundle['items'] as &$item) {
            if ($item['stock'] < $bundle['stock']) $bundle['stock'] = $item['stock'];
            $bundle['raw_price'] += $item['price'];
            $item_options = $db->query("SELECT * FROM Item_options WHERE item_id = ? AND option_stock > 0", [$item['item_id']])->fetchAll();
            $item['option'] = sizeof($item_options) > 0 ? ['options'=>$item_options] : false;
            if (isset($item['image']) && $item['image'])
                $item['image_path'] = "/serve/" . SHOP_ASSET_PATH . "images/" . str_replace(".", "/", $item['image']);

        }
        $bundle['saving'] = $bundle['raw_price'] - $bundle['price'];
        $bundle['disp_price'] = number_format($bundle['price'], 2);
        $bundle['is_bundle'] = true;
        if ($bundle['stock'] > 0) {
            $return_bundles[] = $bundle;
        }
    }
    return $return_bundles;
}