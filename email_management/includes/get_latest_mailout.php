<?php

function getLatestMailout($db) {
    $query = "SELECT date, subject, heading, body, DATE_FORMAT(date, '%y%m%d') AS mailout_id FROM mailouts ORDER BY date DESC LIMIT 1";
    return $db->query($query)->fetch();
}