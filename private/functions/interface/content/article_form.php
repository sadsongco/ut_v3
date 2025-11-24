<?php

require_once(__DIR__."/includes/privateIncludes.php");

try {
    $tabs = getTabs($db);
    $posters = getPosters($db);
}
catch (Exception $e) {
    die ($e->getMessage());
}

echo $m->render("articleForm", ["default_date"=>date('Y-m-d\TH:i'), "tabs"=>$tabs, "posters"=>$posters]);

?>