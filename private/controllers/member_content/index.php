<?php

use Database\Database;
$db = new Database('content');

echo $this->renderer->render('member_content/index', [
    "stylesheets"=>["members", "user"],
    'v'=>$v
 ]);