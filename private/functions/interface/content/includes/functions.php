<?php

function parseLinks($line) {
    $links = [];
    preg_match_all('/({{link}}([^}]*){{\/link}})/', $line, $links);
    $replacements = [];
    foreach ($links[0] as $key=>$link) {
        $replacements[] = ["search"=>$links[0][$key], "replace"=>$links[2][$key]];
    }
    if (sizeof($replacements)==0) return $line;
    foreach ($replacements as $replace) {
        $replace_arr = explode("::", $replace);
        if (sizeof($replace_arr) == 1) $link_text = $link_url = $replace_arr[0];
        else {
            $link_text = $replace_arr[0];
            $link_url = $replace_arr[1];
        }
        $html_replace = $m->render('link', ["link_text"=>$link_text, "link_url"=>$link_url]);
        $html_replace = "BLAAAAA";
        $line = str_replace($replace["search"], $html_replace, $line);
    }
    return $line;
}

function parseBody($content) {
    $body = "<p>";
    for ($x = 0; $x < sizeof($content); $x++) {
        if ($content[$x] == "" || $content[$x] == "\n") continue;
        $content[$x] = parseLinks($content[$x]);
        // $content[$x] = replaceImageTags($content[$x]);
        if ($x+1 < sizeof($content) && ($content[$x+1] == "" || $content[$x+1] == "\n")) {
            $body .= trim($content[$x])."</p>\n<p>";
            continue;
        }
        $body .= trim($content[$x])."<br />\n";
    }
    $body .= "</p>";
    return $body;
}