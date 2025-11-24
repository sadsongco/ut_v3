<?php

include_once("includes/resource_includes.php");
$handle = opendir(base_path(RESOURCE_ASSET_PATH));
$params = [];
while (false !== ($resource_dir = readdir($handle))) {
    $this_params = [];
    if (substr($resource_dir, 0, 1) == ".") continue;
    if (!is_dir(base_path(RESOURCE_ASSET_PATH . $resource_dir))) continue;
    $this_params["dir"] = $resource_dir;
    $this_params["dir_disp"] = ucwords(str_replace("_", " ", $resource_dir));
    $subhandle = opendir(base_path(RESOURCE_ASSET_PATH . $resource_dir));
    $meta = false;
    $meta_key = "";
    while (false !== ($file = readdir($subhandle))) {
        if (substr($file, 0, 1) == ".") continue;
        $filename_array = explode(".", $file);
        $ext = array_pop($filename_array);
        if ($ext == "txt") {
            $meta = true;
            $meta_key = $filename_array[0];
        }
    }
    if ($meta) {
        $this_params["fields"] = $txt_map[$meta_key];
        $this_params["meta_file"] = $meta_key;
    }
    closedir($subhandle);
    $params[] = $this_params;
}
closedir($handle);
echo $m->render("resourceManagement", ["resources"=>$params]);
