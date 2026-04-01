<?php

echo $this->renderer->render('resources/index', ["resources"=>true, "stylesheets"=>["resources"], "scripts"=>["resources/scrollToSection"]]);