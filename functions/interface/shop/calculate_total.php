<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo number_format($_SESSION['subtotal'] + $_SESSION['shipping'], 2);