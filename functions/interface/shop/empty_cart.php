<?php

if(session_status() === PHP_SESSION_NONE) session_start();

$_SESSION['items'] = [];

session_destroy();

header("HX-Trigger: cartUpdated");