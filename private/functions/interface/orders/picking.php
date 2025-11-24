<?php

include_once(__DIR__."/includes/order_includes.php");

try {
    $query = "SELECT
        Items.item_id,
        Items.name,
        SUM(New_Order_items.quantity) AS quantity
    FROM New_Order_items
        JOIN Items ON New_Order_items.item_id = Items.item_id
        JOIN New_Orders ON New_Order_items.order_id = New_Orders.order_id
        AND New_Orders.dispatched IS NULL
        AND New_Orders.transaction_id IS NOT NULL
    GROUP BY Items.item_id";
    $result = $db->query($query)->fetchAll();
} catch (PDOException $e) { 
    echo $e->getMessage();
}

foreach ($result as &$item) {
    $query = "SELECT
        Item_options.option_name
    FROM
        Item_options
    JOIN New_Order_items ON New_Order_items.option_id = Item_options.item_option_id
    WHERE New_Order_items.item_id = ?";
    $params = [$item['item_id']];
    $option = $db->query($query, $params)->fetch();
    if($option) {
        $item['option_name'] = $option['option_name'];
        continue;
    }
}

echo $m->render("picking", ["items"=>$result]);