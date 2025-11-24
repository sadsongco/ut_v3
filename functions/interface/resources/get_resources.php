<?php

require_once(__DIR__ . "/../../../functions/functions.php");
include_once(base_path("../lib/mustache.php-main/src/Mustache/Autoloader.php"));

Mustache_Autoloader::register();

$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('views/resources')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('views/resources/partials'))
));

include("get_resource.php");

$resource_sections = [];
$handle = opendir(base_path(RESOURCE_ASSET_PATH));
while ($sub_dir = readdir($handle)) {
    if (substr($sub_dir, 0, 1) == ".") continue;
    $resource_sections[] = $sub_dir;
}

closedir($handle);
sort($resource_sections);
$sections = [];
$resources = [];
foreach ($resource_sections AS $resource_section) {
    $sections[] = ["section_id"=>$resource_section, "disp_name"=>ucwords(str_replace("_", " ", $resource_section))];
    $resources[] = getResource($resource_section, base_path(RESOURCE_ASSET_PATH));
}
// p_2($resources);
echo $m->render('resourcePage',["sections"=>$sections, "resources"=>$resources]);
// echo $m->render('resourceSection', $resources);