<?php
use Database\Database;
$mem_db = new Database('members');

// templating
$m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/member_content/')),
    'partials_loader' => new Mustache_Loader_FilesystemLoader(base_path('private/views/member_content/partials/'))
));