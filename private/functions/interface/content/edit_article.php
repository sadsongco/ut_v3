<?php

require_once(__DIR__."/includes/privateIncludes.php");

$content = [];
$tabs = getTabs($db);
$posters = getPosters($db);

if ($_POST['edit_article'] != 'null') {
    try {
        $query = "SELECT * FROM articles WHERE article_id = ?";
        $content = $db->query($query, [$_POST['edit_article']])->fetch();
    }
    catch (PDOException $e) {
        die ($e->getMessage());
    }
   foreach ($tabs as &$tab) {
       if (intval($tab['tab_id']) == intval($content['tab'])) $tab['selected'] = 1;
   }
   foreach($posters as &$poster) {
       if ($poster['name'] == $content['posted_by']) $poster['selected'] = 1;
   }
}
    
echo $m->render("articleForm", [
    "default_date"=>date('Y-m-d\TH:i'),
    "content"=> $content,
    "tabs"=>$tabs,
    "posters"=>$posters
]);

?>