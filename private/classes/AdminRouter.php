<?php

namespace Router;

include('../classes/Router.php');

class AdminRouter extends Router
{
    private $renderer;
    private $routes = [
        '' => [
            'name' => 'Admin Index',
            'controller' => 'index'
        ],
        'stock' => [
            'name' => 'Stock Management',
            'controller' => 'stock/index'
        ],
        'orders' => [
            'name' => 'Order Management',
            'controller' => 'orders/index'
        ],
        'content' => [
            'name' => 'Content Management',
            'controller' => 'content/index'
        ],
        'mailout' => [
            'name' => 'Mailout Management',
            'controller' => 'mailout/index'
        ],
        'resources' => [
            'name' => 'Resources Management',
            'controller' => 'resources/index'
        ],
        'utility' => [
            'name' => 'Utilities',
            'controller' => 'utility/index'
        ]
    ];
    function __construct($renderer)
    {
        $this->renderer = $renderer;
        $uri = parse_url($_SERVER['REQUEST_URI'])['path'];
        $paths = explode('/', $uri);
        array_shift($paths);
        $base_path = array_shift($paths);
        $path = array_shift($paths);
        if (array_key_exists($path, $this->routes)) {
            $this->routes[$path]['active'] = true;
            $this->nav = ['endpoints'=>[]];
            foreach ($this->routes AS $route) {
                $this->nav['endpoints'][] = $route;
            }
            require base_path('private/controllers/' . $this->routes[$path]['controller'] . '.php');
        } else {
            $this->nav = [];
            foreach ($this->routes AS $route) {
                $this->nav[] = $route;
            }
            $this->abort();
        }
    }
    function abort($code = 404) 
    {
        http_response_code($code);
        require base_path('controllers/abort.php');
        die();
    }
}