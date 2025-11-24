<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function calculateCartSubtotal($cart_contents)
{
    $subtotal = 0;
    foreach ($cart_contents['items'] as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    foreach ($cart_contents['bundles'] as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    $_SESSION['subtotal'] = $subtotal;
    return number_format($subtotal, 2);
}