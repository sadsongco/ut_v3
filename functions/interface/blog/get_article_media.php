<?php

function makeUniqueToken($auth, $track) {
    return;
    return hash('sha1', $auth->getUsername().$track["filename"]);
}

function getMediaArr($table, $id, $db) {
    $id_id = $table == "media" ? "media_id" : "image_id";
    $query = "SELECT $id_id, filename, title, notes FROM $table WHERE $id_id = ?";
    $result = $db->query($query, [$id])->fetch();
    return $result;
}

function removeExpiredStreamingTokens($db) {
    $query = "DELETE FROM streaming_tokens WHERE timestamp < ?;";
    $db->query($query, [time()-(60*30)]); // remove timestamps longer than 30 minutes ago
}

function getAudio($audio_id, $db, $auth) {
    $track = getMediaArr("media", $audio_id, $db);
    $track["token"] = makeUniqueToken($auth, $track);
    try {
        $query = "INSERT INTO streaming_tokens VALUES (0, ?, ?, ?)
        ON DUPLICATE KEY UPDATE timestamp = ?;";
        $db->query($query, [$track["token"], $track["media_id"], time(), time()]);
    }
    catch (PDOException $e) {
        die($e->getMessage());
    }
    $track["title"] = str_replace(" ", "_", $track["title"]);
    $track["notes"] = str_replace(" ", "_", $track["notes"]);
    return $track;
}

function getImage($image_id, $image_float, $db) {
    $image = getMediaArr("images", $image_id, $db);
    $image["path"] = ARTICLE_ASSET_PATH . "images/" . $image["filename"];
    $image["thumbpath"] = "/serve/" . ARTICLE_ASSET_PATH . "images/thumbnail/" . str_replace(".", "/", $image["filename"]);
    $image["url"] = "/serve/" . ARTICLE_ASSET_PATH . "images/" . str_replace(".", "/", $image["filename"]);
    $image_metadata = getimagesize(base_path(WEB_ASSET_PATH . $image["path"]));
    $image["size_string"] = $image_metadata[3];
    $image["aspect_ratio"] = $image_metadata[0] . "/".$image_metadata[1];
    $image["template"] = "articles/block_image";
    if ($image_float) {
        switch ($image_float) {
            case "l":
                $image["float"] = "floatLeft";
                $image["template"] = "articles/inline_image";
                break;
            case "r":
                $image["template"] = "articles/inline_image";
                $image["float"] = "floatRight";
                break;
            default:
                $image["float"] = "floatCentered";
        }
    }
    return $image;
}

function getMedia($line, $db, $auth, $m, $host) {
        // Get audio
        preg_match_all('/{{a::([0-9])+}}/', $line, $audio_ids);
        if (sizeof($audio_ids[1]) > 0) {
            $nl_flag = false;
            foreach ($audio_ids[1] as $key=>$audio_id) {
                $track = getAudio($audio_id, $db, $auth);
                $replace_el = $m->render("articles/audio_loader", ["track"=>json_encode($track), "base_dir"=>$host]);
                $replace_str = $audio_ids[0][$key];
                $line = preg_replace("/$replace_str/", $replace_el, $line);
            }
        }
        // get images
        preg_match_all('/{{i::(([0-9])+)(?:::)?(l|r)?.?}}/', $line, $image_ids);
        if (sizeof($image_ids[1]) > 0) {
            $nl_flag = false;
            foreach ($image_ids[1] as $key=>$image_id) {
                $image = getImage($image_id, $image_ids[2][$key] != "" ? $image_ids[2][$key] : false , $db);
                $replace_el = $m->render($image["template"], $image);
                $replace_str = $image_ids[0][$key];
                $line = preg_replace("/$replace_str/", $replace_el, $line);    
            }
        }
        preg_match_all('/{{i::([0-9])+(?:::)?(l|r)?.?}}/', $line, $image_ids);
        $line = trim($line);
        return $line;
}

function parseLinks($line, $m) {
    $links = [];
    preg_match_all('/({{link}}([^}]*){{\/link}})/', $line, $links);
    $replacements = [];
    foreach ($links[0] as $key=>$link) {
        $replacements[] = ["search"=>$links[0][$key], "replace"=>$links[2][$key]];
    }
    if (sizeof($replacements)==0) return $line;
    foreach ($replacements as $replace) {
        $replace_arr = explode("::", $replace['search']);
        $replace_arr = (preg_replace('/{{\/?link}}/', "", $replace_arr));
        if (sizeof($replace_arr) == 1) $link_text = $link_url = $replace_arr[0];
        else {
            $link_text = $replace_arr[0];
            $link_url = $replace_arr[1];
        }
        $html_replace = $m->render('link', ["link_text"=>$link_text, "link_url"=>$link_url]);
        $line = str_replace($replace["search"], $html_replace, $line);
    }
    return $line;
}

function parseBody($body, $db, $auth, $m, $host) {
    $content = explode("\n", str_replace("\n\r", "\n", $body));
    // removeExpiredStreamingTokens($db);
    $output = "<p>";
    for ($x = 0; $x < sizeof($content); $x++) {
        if ($content[$x] == "" || $content[$x] == "\n") continue;
        $content[$x] = parseLinks($content[$x], $m);
        $content[$x] = getMedia($content[$x], $db, $auth, $m, $host);
        if ($x+1 < sizeof($content) && ($content[$x+1] == "" || $content[$x+1] == "\n")) {
            $output .= trim($content[$x])."</p>\n<p>";
            continue;
        }
        $output .= trim($content[$x])."<br />\n";
    }
    $output .= "</p>";
    return $output;
}

?>