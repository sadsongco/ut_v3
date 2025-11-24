<?php

use Database\Database;
$db = new Database('orders');

$query = "SELECT * FROM Items";
$items = $db->query($query)->fetchAll();

$query = "SELECT bundle_id, price, IF (active = 1, 'checked', null) AS active FROM Bundles";
$bundles = $db->query($query)->fetchAll();
foreach ($bundles as &$bundle) {
    $query = "SELECT Items.name FROM Bundle_items JOIN Items ON Bundle_items.item_id = Items.item_id WHERE Bundle_items.bundle_id = ?";
    $bundle['items'] = $db->query($query, [$bundle['bundle_id']])->fetchAll();
    $query = "SELECT
        MIN(Items.stock + (SELECT IFNULL(SUM(Item_options.option_stock), 0) FROM Item_options WHERE Item_options.item_id = Items.item_id)) as stock
    FROM Bundle_items JOIN Items ON Bundle_items.item_id = Items.item_id WHERE Bundle_items.bundle_id = ?";
    $bundle['stock'] = $db->query($query, [$bundle['bundle_id']])->fetch()['stock'];
}


$query = "SELECT DISTINCT category FROM Items";
$categories = $db->query($query)->fetchAll();

$packaging_classifications = [
    ["name"=>"CD", "selected"=>false],
    ["name"=>"LP", "selected"=>false],
    ["name"=>"SHIRT", "selected"=>false],
    ["name"=>"POSTER", "selected"=>false],
    ["name"=>"OTHER", "selected"=>false],
];

foreach ($items as &$item) {
    $item['packaging_options'] = $packaging_classifications;
    foreach($item['packaging_options'] as &$classification) {
        if ($classification['name'] == $item['packaging_classification']) {
            $classification['selected'] = " selected";
        }
    }
    $item['featured'] = $item['featured'] == 1 ? "checked" : null;
    $item['e_delivery'] = $item['e_delivery'] == 1 ? "checked" : null;
    $item['categories'] = $categories;
    foreach ($item['categories'] as &$category) {
        $category['selected'] = "";
        if ($category['category'] == $item['category']) {
            $category['selected'] = "selected";
        }
    }
    $query = "SELECT * FROM Item_options WHERE item_id = ?";
    $item['options'] = $db->query($query, [$item['item_id']])->fetchAll();
}

echo $this->renderer->render('stock/index', ["items"=>$items, "bundles"=>$bundles, "categories"=>$categories, "packaging_options"=>$packaging_classifications, "stylesheets"=>["stock"]]);