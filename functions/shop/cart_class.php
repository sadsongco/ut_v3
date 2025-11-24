<?php

session_start();

if ((isset($_SESSION['bundles']) && sizeof($_SESSION['bundles']) > 0 ) || (isset($_SESSION['items']) && sizeof($_SESSION['items'])) > 0) {
    $no_items = 0;
    if (isset($_SESSION['items'])) {
        foreach ($_SESSION['items'] AS $item) {
            $no_items += $item['quantity'];
        }
    }
    if (isset($_SESSION['bundles'])) {
        // $no_items += sizeof($_SESSION['bundles']);
        foreach ($_SESSION['bundles'] AS $bundle) {
            $no_items += $bundle['quantity'];
        }
    }
    echo '<a class="icon viewCartItems" href="/shop/cart"><div class="viewCartBadge">' . $no_items . '</div></a>';
}

else echo '<a class="icon viewCart" href="/shop/cart"></a>';