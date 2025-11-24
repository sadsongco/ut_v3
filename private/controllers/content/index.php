<?php

use Database\Database;
$db = new Database('content');

echo $this->renderer->render('content/index', ["stylesheets"=>["articles"]]);