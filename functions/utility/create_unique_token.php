<?php

function createUniqueToken($input) {
    $output = false;
    $encrypt_method = DOWNLOAD_CIPHER;
    $iv = substr(hash('sha256', DOWNLOAD_SALT), 0, 16);
    $output = openssl_encrypt($input, $encrypt_method, DOWNLOAD_SALT, 0, $iv);
    $output = base64_encode($output);
    return $output;
}