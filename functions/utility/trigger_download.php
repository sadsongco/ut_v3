<?php

function triggerDownload($filename, $file_path, $d=false)
{
    if (!is_file($file_path)) exit("There was an error. Please try again or contact info@unbelievabletruth.co.uk");
    try {
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private",false);
    header("Content-Type: " . mime_content_type($file_path));
    if ($d)
        header("Content-Disposition: inline; filename=".$filename);
    else
        header("Content-Disposition: attachment; filename=".$filename);
    header("Content-Transfer-Encoding: binary");
    header("Accept-Ranges: bytes");
    header("Content-Length: ".filesize($file_path));
    header('Last-Modified: '.date(DATE_RFC2822, filemtime($file_path)));
    readfile($file_path);
}
catch (Exception $e) {
    error_log($e);
    exit("There was an error. Please try again or contact info@unbelievabletruth.co.uk");
}
}