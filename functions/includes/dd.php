<?php

function dd($var)
{
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    die();
}

function p_2($var)
{
    echo '<pre>';
    print_r($var);
    echo '</pre>';
}