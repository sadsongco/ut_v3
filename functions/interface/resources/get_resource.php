<?php

function sortResourcesByName($a, $b) {
    if ($a['resource'] == $b['resource']) {
        return 0;
    }
    return ($a['resource'] < $b['resource']) ? -1 : 1;
}

function getSouncloudPlaylists($path) {
    $file_string = file_get_contents(base_path(RESOURCE_ASSET_PATH . $path."soundcloud_playlists.txt"));
    $playlist_arr = explode("\n", $file_string);
    $playlists = [];
    foreach ($playlist_arr as $playlist) {
        if ($playlist == "") continue;
        $playlist_data = explode("|", $playlist);
        $playlists[] = [
            "playlist_title"=>$playlist_data[0],
            "playlist_id"=>$playlist_data[1],
            "secret_token"=>$playlist_data[2]
        ];
    }
    return $playlists;
}

function getYouTubeVideos($path) {
    $file_string = file_get_contents(base_path(RESOURCE_ASSET_PATH . $path."youtube_videos.txt"));
    $videeos_arr = explode("\n", $file_string);
    $videos = [];
    foreach ($videeos_arr as $video) {
        if ($video == "") continue;
        $video_data = explode("|", $video);
        $videos[] = [
            "video_title"=>$video_data[0],
            "yt_id"=>$video_data[1],
            "yt_si"=>$video_data[2],
            "url"=>$video_data[3]
        ];
    }
    return $videos;
}

function getPressShotList($path) {
    $file_string = file_get_contents(base_path(RESOURCE_ASSET_PATH . $path."press_shots.txt"));
    $photos_arr = explode("\n", $file_string);
    $photos = [];
    foreach ($photos_arr as $photo) {
        if ($photo == "") continue;
        $photo_data = explode("|", $photo);
        $serve_path = str_replace(".", "/", $photo_data[0]);
        $photos[] = [
            "resource"=>$photo_data[0],
            "full_res_url"=>$path."full_res/".$serve_path,
            "web_url"=>$path."web/".$serve_path,
            "thumbnail_url"=>$path."thumbnail/".$serve_path,
            "photo_credit"=>$photo_data[1]
        ];
    }
    foreach ($photos as &$photo) {
        $full_res_size = getimagesize(base_path(RESOURCE_ASSET_PATH . $path."full_res/".$photo["resource"]));
        $photo["full_res_width"] = $full_res_size[0];
        $photo["full_res_height"] = $full_res_size[1];
        $web_size = getimagesize(base_path(RESOURCE_ASSET_PATH . $path."web/".$photo["resource"]));
        $photo["web_width"] = $web_size[0];
        $photo["web_height"] = $web_size[1];
        $thumbnail_size = getimagesize(base_path(RESOURCE_ASSET_PATH . $path."thumbnail/".$photo["resource"]));
        $photo["thumbnail_width"] = $thumbnail_size[0];
        $photo["thumbnail_height"] = $thumbnail_size[1];
    }

    return $photos;
}

function getPressQuotes($path) {
    $file_string = file_get_contents(base_path(RESOURCE_ASSET_PATH . $path."press_quotes.txt"));
    $quotes_arr = explode("\n", $file_string);
    $quotes = [];
    foreach ($quotes_arr as $quote) {
        if ($quote == "") continue;
        $quote_data = explode("|", $quote);
        $quotes[] = [
            "quote"=>$quote_data[0],
            "source"=>$quote_data[1]
        ];
    }
    return $quotes;
}

function getResource($section) {
    $output = [];
    $sub_dir = '';
    
    if ($section == 'press_shots' || $section == 'artwork') $sub_dir = 'full_res/';
    
    $path = $section.'/';
    $output['section'] = $section;
    $output['name'] = ucwords(str_replace("_", " ", $section));
    $output['resources'] = [];
    
    try {
        if ($section == 'playlists') {
            $output['resources'] = ['playlists'=>getSouncloudPlaylists($path), "ext_media"=>true];
            return $output;
        }
        if ($section == "videos") {
            $output['resources'] = ['videos'=>getYouTubeVideos($path), "ext_media"=>true];
            return $output;
        }
        if ($section == "press_shots") {
            $output['resources'] = ["press_shots"=>getPressShotList($path, $sub_dir), "ext_media"=>true];
            return $output;
        }
        if ($section == "press_quotes") {
            $output['resources'] = ["press_quotes"=>getPressQuotes($path, $sub_dir), "ext_media"=>true];
            return $output;
        }
        if ($handle = opendir(base_path(RESOURCE_ASSET_PATH . $path . $sub_dir))) {
            while (false != ($entry = readdir($handle))) {
                if (substr($entry, 0, 1) == '.') continue;
                $resource = ["path"=>$section, "resource"=>$entry, "serve_path"=>str_replace(".", "/", $entry)];
                if ($section == 'artwork') {
                    $resource['full_res_size'] = getimagesize(base_path(RESOURCE_ASSET_PATH . $path."full_res/".$entry));
                    $resource['web_size'] = getimagesize(base_path(RESOURCE_ASSET_PATH . $path."web/".$entry));
                    $resource['thumbnail_size'] = getimagesize(base_path(RESOURCE_ASSET_PATH . $path."thumbnail/".$entry));
                    $resource['img_preview'] = true;
                }
                if ($section == 'logos') {
                    $resource['logo_size'] = getimagesize(base_path(RESOURCE_ASSET_PATH . $path . $entry));
                    $resource['logo_preview'] = true;
                }
                $output['resources'][] = $resource;
            }
            closedir($handle);
        }
    
    } catch (Exception $e) {
        $output['success'] = false;
        $output['error'] = $e->getMessage();
    }

    usort($output['resources'], 'sortResourcesByName');

    return $output;
}