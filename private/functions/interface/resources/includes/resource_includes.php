<?php

include(__DIR__ . "/../../../../../functions/functions.php");
require base_path('../lib/mustache.php-main/src/Mustache/Autoloader.php');
Mustache_Autoloader::register();

$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/resources')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/resources/partials'))
));

$txt_map = [
    "soundcloud_playlists" => [
        "name",
        "id",
        "secret"
    ],
    "youtube_videos" => [
        "name",
        "id",
        "secret",
        "url"
    ],
    "press_shots" => [
        "credit"
    ]
];

$update_map = [
    "soundcloud_playlists" => [
        "name",
        "id",
        "secret"
    ],
    "youtube_videos" => [
        "name",
        "id",
        "secret",
        "url"
    ],
    "press_shots" => [
        "file",
        "credit"
    ]
];

$resize_resources = [
    "artwork",
    "press_shots"
];

define("MAX_IMAGE_WIDTH", 900);
define("IMAGE_THUMBNAIL_WIDTH", 200);