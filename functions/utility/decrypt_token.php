<?php

function decryptUniqueToken($token) {
    $encrypt_method = DOWNLOAD_CIPHER;
    $iv = substr(hash('sha256', DOWNLOAD_SALT), 0, 16);
    return openssl_decrypt(base64_decode($token), $encrypt_method, DOWNLOAD_SALT, 0, $iv);
}