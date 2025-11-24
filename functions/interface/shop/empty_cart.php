<?php

session_start();

$_SESSION['items'] = [];

session_destroy();

header("HX-Trigger: cartUpdated");