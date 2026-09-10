<?php

include(__DIR__ . "/../../../../functions/functions.php");
include(base_path('functions/interface/members/article_render.php'));

echo renderArticle($_POST["content_heading"], $_POST["content_body"], $_POST["content_pub_date"]);
