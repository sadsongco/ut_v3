<?php

if(session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['tariff'])) echo number_format($_SESSION['subtotal'] + $_SESSION['shipping'] + $_SESSION['tariff'], 2);
else echo number_format($_SESSION['subtotal'] + $_SESSION['shipping'], 2);