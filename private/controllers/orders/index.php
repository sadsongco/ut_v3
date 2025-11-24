<?php

echo $this->renderer->render('orders/index', ["stylesheets"=>["orders"], "v"=>random_int(0, 1000)]);