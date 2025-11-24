<?php

function getHost() {
    if (!isset($_SERVER['HTTP_HOST']) || !$_SERVER['HTTP_HOST']) return "https://unbelievabletruth.co.uk";
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') $protocol .= 's';
    return "$protocol://".$_SERVER['HTTP_HOST'];
}

?>