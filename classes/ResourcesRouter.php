<?php

namespace Router;

include_once('Router.php');

class ResourcesRouter extends Router
{
    private $renderer;
    private $routes = [
        '/' => [
            'name' => 'Resources',
            'controller' => 'resources/index'
        ],
        'serve' => [
            'name' => 'Serve',
            'controller' => 'resources/serve'
        ]
    ];
    function __construct($renderer)
    {
        $this->renderer = $renderer;
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = str_replace('/resources', '', $uri);
        if (array_key_exists($uri, $this->routes)) {
            $this->routes[$uri]['active'] = true;
            $this->nav = ['endpoints'=>[]];
            foreach ($this->routes AS $route) {
                $this->nav['endpoints'][] = $route;
            }
            require base_path('controllers/' . $this->routes[$uri]['controller'] . '.php');
            exit();
        }
        $paths = explode('/', $uri);
        if (isset($paths[1]) && array_key_exists($paths[1], $this->routes)) {
            require base_path('controllers/' . $this->routes[$paths[1]]['controller'] . '.php');
            exit();
        }
        $this->abort();
    }

    function abort($code = 404) 
    {
        http_response_code($code);
        require base_path('controllers/abort.php');
        die();
    }
}