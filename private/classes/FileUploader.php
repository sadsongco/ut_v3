<?php

namespace FileUploader;

class FileUploader
{
    protected $upload_dir;
    protected $files_to_upload;
    protected $response;
    private $subdirs = [
        "jpeg" => "images",
        "jpg" => "images",
        "png" => "images",
        "gif" => "images",
        "mp3" => "audio",
        "pdf" => "pdf",
        "wav" => "audio_fullres"
    ];
    private $uploaded_file;
    private $upload_path;
    private $image_fnc;
    private $image;
    private $max_width;
    private $thumb_width;
    private $resources;
    private $web_path;

    public function __construct($path, $resources=false, $max_width=false, $thumb_width=80) {
        $this->upload_dir = base_path($path);
        $this->max_width = $max_width;
        $this->thumb_width = $thumb_width;
        $this->response = [];
        $this->resources = $resources;

        if (!isset($_FILES) || !isset($_FILES["files"]) || sizeof($_FILES) == 0 || sizeof($_FILES["files"]) == 0) {
            $this->response = ["success"=>false, "message"=>"No files uploaded"];
            return $this;
        } else {
            $this->files_to_upload = $_FILES["files"];
            return $this;
        }
    }

    public function checkFileSizes() {
        if (isset($this->response["success"]) && !$this->response["success"]) return $this;
        $post_size = 0;
        foreach ($this->files_to_upload["name"] as $key=>$filename) {
            if ($this->files_to_upload["size"][$key] > MAX_FILE_SIZE) {
                unset($this->files_to_upload["tmp_name"][$key]);
                $this->response[] = ["success"=> false, "message"=>"File $filename is too big"];
                continue;
            }
            $post_size += $this->files_to_upload["size"][$key];
            if ($post_size > MAX_POST_SIZE) {
                $this->response[] = ["success"=> false, "message"=>"File upload size too big - try doing them one at a time"];
                return false;
            }        
        }
        return $this;
    }

    public function uploadFiles() {
        if (isset($this->response["success"]) && !$this->response["success"]) return $this;
        foreach($this->files_to_upload["name"] as $key=>$filename) {
            if (!isset($this->files_to_upload["tmp_name"][$key])) {
                $this->response[] = ["success"=>false, "message"=>"NO TMP_NAME:.."];
                continue;
            }
            $this->uploaded_file = $this->files_to_upload["tmp_name"][$key];
            if ($this->uploaded_file == "") {
                $this->response[] = ["success"=>false, "message"=>"NO TMP_NAME:.."];
                continue;
            }
            $image_file_type = strtolower(pathinfo($filename,PATHINFO_EXTENSION));
            if (!in_array($image_file_type, array_keys($this->subdirs))) {
                $this->response[] = ["success"=> false, "message"=>$filename.": $image_file_type file types are not supported"];
                return $this;   
            }
            $subdir = $this->subdirs[$image_file_type];
            $filename = str_replace(" ", "_", $filename);
            $target_dir = $this->resources ? $this->upload_dir ."/"  : $this->upload_dir . $subdir . "/";
            if (!is_dir($target_dir)) mkdir($target_dir);
            $this->upload_path = $target_dir . $filename;
            if ($this->resources) {
                if (!$this->thumb_width) {
                    $upload_dir = $target_dir;
                    $this->web_path = false;
                } else {
                    $upload_dir = $target_dir . "full_res/";
                    $this->web_path = $target_dir . "web/";
                }
                if (!is_dir($upload_dir)) mkdir($upload_dir);
                $this->upload_path  = $upload_dir . $filename;
            }
            if (file_exists($this->upload_path)) {
                $this->response[] = ["success"=>false, "message"=>$filename." already exists", "filename"=>$filename];
                continue;
            }
            if ($subdir === "images") {
                $this->image = $this->createImage($this->uploaded_file, $image_file_type);
                if (!$this->image) {
                    $this->response[] = ["success"=>false, "message"=>$filename." is not an image"];
                }
                $this->resizeImage($this->max_width);
                if (($this->image_fnc)($this->image, $this->upload_path)) {
                    if ($this->web_path) {
                        $this->makeWebImage($filename);
                    }
                    $this->makeThumbnail($filename);
                    $this->response[] = [
                        "success"=>true,
                        "message"=>$filename." uploaded",
                        "filename"=>$filename,
                        "type"=>$subdir,
                        "key"=>$key
                    ];
                }
                imagedestroy($this->image);
            }
            else {
                if (move_uploaded_file($this->uploaded_file, $this->upload_path)) {
                    $this->response[] = [
                        "success"=>true,
                        "message"=>$filename." uploaded",
                        "filename"=>$filename,
                        "type"=>$subdir,
                        "key"=>$key
                    ];
                }
            }
        }
        return $this;
    }

    private function createImage($uploaded_file, $image_file_type) {
        $image = null;
        $image_fnc = "";
        switch ($image_file_type) {
            case "jpg":
            case "jpeg":
                $this->image_fnc = "imagejpeg";
                return imagecreatefromjpeg($uploaded_file);
                break;
            case "png":
                $this->image_fnc = "imagepng";
                return imagecreatefrompng($uploaded_file);
                break;
            case "gif":
                $this->image_fnc = "imagegif";
                return imagecreatefromgif($uploaded_file);
                break;
            default:
                return false;
        }
    }

    private function resizeImage() {
        if (!$this->max_width) return;
        $image_size = getimagesize($this->uploaded_file);        
        if ($image_size[0] <= $this->max_width) return;
        $this->image = imagescale($this->image, $this->max_width);
    }

    private function makeThumbnail($filename) {
        if (!$this->thumb_width) return;
        $thumbnail_dir = $this->resources ? $this->upload_dir . "/thumbnail/" : $this->upload_dir . "images/thumbnail/";
        if (!is_dir($thumbnail_dir)) mkdir($thumbnail_dir);
        $path = $thumbnail_dir . $filename;
        $this->image = imagescale($this->image, $this->thumb_width);
        ($this->image_fnc)($this->image, $path);
    }

    private function makeWebImage($filename) {
        if (!$this->web_path) return;
        $web_dir = $this->web_path;
        if (!is_dir($web_dir)) mkdir($web_dir);
        $path = $web_dir . $filename;
        $this->image = imagescale($this->image, RESOURCE_WEB_WIDTH);
        ($this->image_fnc)($this->image, $path);
    }

    public function getResponse() {
        return $this->response;
    }
}