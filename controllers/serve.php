<?php

include_once(__DIR__ . "/../functions/functions.php");
include(base_path("functions/utility/trigger_download.php"));

$inline = false;

$filetype = array_pop($paths);
if ($filetype === "inline") {
  $inline = true;
  $filetype = array_pop($paths);
}

foreach ($paths as $path) {
    if ($path === "." || $path === "..") {
      exit("NOT A VALID RESOURCE");  
    }
}
$file_path = base_path(WEB_ASSET_PATH . implode("/", $paths) . "." . $filetype);
p_2($file_path);
exit();
$filename = array_pop($paths) . "." . $filetype;

triggerDownload($filename, $file_path, $inline);