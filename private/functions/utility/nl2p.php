<?php
function nl2p($string) {
    $arr = explode("\n", $string);
    return "<p>" .implode("</p>\n<p>", $arr) . "</p>";
}