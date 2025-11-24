<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo number_format($_SESSION['subtotal'], 2);