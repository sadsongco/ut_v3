<?php

include(__DIR__ . "/../../../../functions/functions.php");
include(base_path('functions/interface/members/render_article.php'));

echo renderArticle($_POST);
