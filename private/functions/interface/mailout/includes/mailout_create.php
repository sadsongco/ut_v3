<?php

include_once(__DIR__ . "/mailout_includes.php");

/* *** FUNCTIONS *** */
function replaceHTMLLink($line) {
    $links = [];
    preg_match_all('/({{link}}([^}]*){{\/link}})/', $line, $links);
    $replacements = [];
    foreach ($links[0] as $key=>$link) {
        $replacements[] = ["search"=>$links[0][$key], "replace"=>$links[2][$key]];
    }
    if (sizeof($replacements)==0) return $line;
    foreach ($replacements as $replace) {
        $html_replace = '<a href="'.$replace["replace"].'">'.$replace["replace"].'</a>';
        $line = str_replace($replace["search"], $html_replace, $line);
    }
    return $line;
}

function createHTMLBody($body, $m, $db) {
    $content = explode("\n", $body);
    $body = "<p>";
    for ($x = 0; $x < sizeof($content); $x++) {
        if ($content[$x] == "" || $content[$x] == "\n") continue;
        $content[$x] = replaceHTMLLink($content[$x]);
        $content[$x] = replaceImageTags($content[$x], $m, $db);
        if ($x+1 < sizeof($content) && ($content[$x+1] == "" || $content[$x+1] == "\n")) {
            $body .= trim($content[$x])."</p>\n<p>";
            continue;
        }
        $body .= trim($content[$x])."<br />\n";
    }
    $body .= "</p>";
    return $body;
}

function createTextBody($body) {
    $content = explode('\n', $body);
    foreach($content as &$line) {
        $line = trim(preg_replace('/{{link}}([^}]*){{\/link}}/', '$1', $line));
        //remove images
        $line = preg_replace_callback('/<!--{{i::([0-9]+)(::)?(l|r)?}}-->/',
        fn ($matches) => "",
        $line);
    }
    return implode("\n", $content);
}

function replaceImageTags($line, $m, $db) {
    $line = preg_replace_callback('/<!--{{i::([0-9]+)(::)?(l|r)?}}-->/',
    fn ($matches) => $m->render('articleImage', getImageData($db, $matches[1], isset($matches[3]) ? $matches[3] : null)),
    $line);
    return $line;
}

function getImageData($db, $img_id, $img_align=null) {
    try {
        $query = "SELECT * FROM MailoutImages WHERE img_id = ?;";
        $result = $db->query($query, [$img_id])->fetch();
        $result['url'] = str_replace(".", "/", $result['url']);
        $result['caption'] = htmlentities($result['caption']);
        $result['host'] =  getHost();
        $result['path'] = "serve/" . MAILOUT_ASSET_PATH . "images/";
    }
    catch (PDOException $e) {
        throw new Exception($e);
    }
    // $html_align = null;
    // switch ($img_align) {
    //     case "l":
    //         $html_align = "left";
    //         break;
    //     case "r":
    //         $html_align = "right";
    //         break;
    // }
    // $result[0]['align'] = $html_align;
    return $result;
}