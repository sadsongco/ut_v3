<?php

require_once(__DIR__."/includes/privateIncludes.php");

try {
    $query = "SELECT article_id, title FROM articles ORDER BY added ASC";
    $result = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
}
catch (PDOException $e) {
    die($e->getMessage());
}

echo $m->render("editArticleForm", ["articles"=>$result]);

?>