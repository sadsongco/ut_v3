<?php

namespace ArticleRenderer;
include_once(__DIR__ . "/../functions/functions.php");

use Database\Database;
use \DateTime;
use \Mustache_Engine;
use \Mustache_Loader_FilesystemLoader;


class ArticleRenderer {

    protected const CONTEXTS = [
        'article' => [
            "db" => "content",
            "media_path" => "",
            "view_path" => "articles",
        ],
        'members' => [
            "db" => "members",
            "media_path" =>  MEMBERS_ASSET_PATH,
            "view_path" => "articles",
        ]
    ];
    private object $db;
    private string $media_path;
    private string $host;
    private array $auth;
    private object $renderer;
    private array $article = [];

    function __construct(array $article, string $context='article')
    {
        $this->host = getHost();
        $this->db = new Database(self::CONTEXTS[$context]["db"]);
        $this->media_path = self::CONTEXTS[$context]["media_path"];
        $this->renderer = new Mustache_Engine(array(
            'loader' => new Mustache_Loader_FilesystemLoader(base_path("views/" . self::CONTEXTS[$context]["view_path"])),
            'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path("views/partials/" . self::CONTEXTS[$context]["view_path"]))
        ));
        $this->article = $article;
        return $this;
    }

    function makeUniqueToken($auth, $track) {
    return;
    return hash('sha1', $auth->getUsername().$track["filename"]);
}

    private function getMediaArr($table, $id) {
        $id_id = $table == "media" ? "media_id" : "image_id";
        $query = "SELECT $id_id, filename, title, notes FROM $table WHERE $id_id = ?";
        $result = $this->db->query($query, [$id])->fetch();
        return $result;
    }

    private function removeExpiredStreamingTokens($db) {
        $query = "DELETE FROM streaming_tokens WHERE timestamp < ?;";
        $db->query($query, [time()-(60*30)]); // remove timestamps longer than 30 minutes ago
    }

    private function getAudio($audio_id, $db, $auth) {
        $track = getMediaArr("media", $audio_id, $db);
        $track["token"] = makeUniqueToken($auth, $track);
        try {
            $query = "INSERT INTO streaming_tokens VALUES (0, ?, ?, ?)
            ON DUPLICATE KEY UPDATE timestamp = ?;";
            $db->query($query, [$track["token"], $track["media_id"], time(), time()]);
        }
        catch (PDOException $e) {
            die($e->getMessage());
        }
        $track["title"] = str_replace(" ", "_", $track["title"]);
        $track["notes"] = str_replace(" ", "_", $track["notes"]);
        return $track;
    }

    private function getImage(int $image_id, string $image_float) {
        $image = $this->getMediaArr("images", $image_id);
        $image["path"] = $this->media_path . "images/" . $image["filename"];
        $image["thumbpath"] = "/serve/" . $this->media_path . "images/thumbnail/" . str_replace(".", "/", $image["filename"]);
        $image["url"] = "/serve/" . $this->media_path . "images/" . str_replace(".", "/", $image["filename"]);
        $image_metadata = getimagesize(base_path(WEB_ASSET_PATH . $image["path"]));
        $image["size_string"] = $image_metadata[3];
        $image["aspect_ratio"] = $image_metadata[0] . "/".$image_metadata[1];
        $image["template"] = "block_image";
        if ($image_float) {
            switch ($image_float) {
                case "l":
                    $image["float"] = "float-left";
                    $image["template"] = "articles/inline_image";
                    break;
                case "r":
                    $image["template"] = "articles/inline_image";
                    $image["float"] = "float-right";
                    break;
                default:
                    $image["float"] = "float-centered";
            }
        }
        return $image;
    }

    private function getMedia(string $line) {
            // Get audio
            preg_match_all('/{{a::([0-9])+}}/', $line, $audio_ids);
            if (sizeof($audio_ids[1]) > 0) {
                $nl_flag = false;
                foreach ($audio_ids[1] as $key=>$audio_id) {
                    $track = getAudio($audio_id, $this->db, $this->auth);
                    $replace_el = $this->renderer->render("audio_loader", ["track"=>json_encode($track), "base_dir"=>$this->host]);
                    $replace_str = $audio_ids[0][$key];
                    $line = preg_replace("/$replace_str/", $replace_el, $line);
                }
            }
            // get images
            preg_match_all('/{{i::(([0-9])+)(?:::)?(l|r)?.?}}/', $line, $image_ids);
            if (sizeof($image_ids[1]) > 0) {
                $nl_flag = false;
                foreach ($image_ids[1] as $key=>$image_id) {
                    $image = $this->getImage($image_id, $image_ids[2][$key] != "" ? $image_ids[2][$key] : false);
                    $replace_el = $this->renderer->render($image["template"], $image);
                    $replace_str = $image_ids[0][$key];
                    $line = preg_replace("/$replace_str/", $replace_el, $line);    
                }
            }
            preg_match_all('/{{i::([0-9])+(?:::)?(l|r)?.?}}/', $line, $image_ids);
            $line = trim($line);
            return $line;
    }

    private function parseLinks(string $line) {
        $links = [];
        preg_match_all('/({{link}}([^}]*){{\/link}})/', $line, $links);
        $replacements = [];
        foreach ($links[0] as $key=>$link) {
            $replacements[] = ["search"=>$links[0][$key], "replace"=>$links[2][$key]];
        }
        if (sizeof($replacements)==0) return $line;
        foreach ($replacements as $replace) {
            $replace_arr = explode("::", $replace['search']);
            $replace_arr = (preg_replace('/{{\/?link}}/', "", $replace_arr));
            if (sizeof($replace_arr) == 1) $link_text = $link_url = $replace_arr[0];
            else {
                $link_text = $replace_arr[0];
                $link_url = $replace_arr[1];
            }
            $html_replace = $this->renderer->render('link', ["link_text"=>$link_text, "link_url"=>$link_url]);
            $line = str_replace($replace["search"], $html_replace, $line);
        }
        return $line;
    }

    private function parseBody(string $body) {
        $content = explode("\n", str_replace("\n\r", "\n", $body));
        // removeExpiredStreamingTokens($db);
        $output = "<p>";
        for ($x = 0; $x < sizeof($content); $x++) {
            if ($content[$x] == "" || $content[$x] == "\n") continue;
            $content[$x] = $this->parseLinks($content[$x]);
            $content[$x] = $this->getMedia($content[$x]);
            if ($x+1 < sizeof($content) && ($content[$x+1] == "" || $content[$x+1] == "\n")) {
                $output .= trim($content[$x])."</p>\n<p>";
                continue;
            }
            $output .= trim($content[$x])."<br />\n";
        }
        $output .= "</p>";
        return $output;
    }

    public function renderArticle() {
        $date = new DateTime($this->article["content_pub_date"]);
        $this->article['date'] = $date->format("jS M Y - g:iA");
        $this->article['content_body'] = $this->parseBody($this->article['content_body']);
        $this->article['content_body'] = nl2br($this->article['content_body']);

        return $this->renderer->render("article", [
            "content" => $this->article
        ]);

    }
}