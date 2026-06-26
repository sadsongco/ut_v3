<?php

include (__DIR__ . "/../functions/functions.php");

$track = $_POST; //VALIDATE?
$audio_path = $track["members"] ? MEMBERS_ASSET_PATH : ARTICLE_ASSET_PATH;
$track["host"] = getHost();
$track["title"] = str_replace("_", " ", $track["title"]);
$track["notes"] = str_replace("_", " ", nl2br($track["notes"]));
$track["path"] = "/serve/" . $audio_path . "audio/" . str_replace(".", "/", $track["filename"]);

echo $m->render("audio_track", $track);