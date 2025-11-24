<?php

include_once("includes/resource_includes.php");

if (isset($_POST["resource_name"]) && $_POST["resource_name"] != "") {
    $dir_name = str_replace(" ", "_", strtolower($_POST["resource_name"]));
    if (is_dir(base_path(RESOURCE_ASSET_PATH . $dir_name))) {
        exit ("<h2>Resource '" . $_POST["resource_name"] . "' already exists</h2>");
    } else {
        mkdir(base_path(RESOURCE_ASSET_PATH . $dir_name));
        file_put_contents(base_path(".gitignore"), "\nresources/resource_dirs/" . $dir_name, FILE_APPEND);
        exit ("<h2>Resource '" . $_POST["resource_name"] . "' added</h2>");
    }
}

echo "<h2>No resource name given</h2>";