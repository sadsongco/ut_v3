<?php

include(__DIR__ . "/../../../../functions/functions.php");

removeDir(base_path(RESOURCE_ASSET_PATH . $_POST['resource_dir']));

function removeDir(string $dir): void {
    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it,
                 RecursiveIteratorIterator::CHILD_FIRST);
    foreach($files as $file) {
        if ($file->isDir()){
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }
    rmdir($dir);
}

echo "<h2>Resource deleted</h2>";