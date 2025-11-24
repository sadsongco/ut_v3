<?php

function makeUniqueToken($email_id, $email) {
    $token = $email_id . $email;
    return hash('sha256', $token);
}