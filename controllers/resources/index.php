<?php


echo $this->renderer->render('resources/index', ["stylesheets"=>["resources"], "scripts"=>["resources/getResources", "resources/scrollToSection"]]);