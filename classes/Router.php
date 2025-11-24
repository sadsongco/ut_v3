<?php

namespace Router;

class Router
{
    private $routes = [
        '' => [
            'path' => '/',
            'controller' => 'index',
            'name' => 'Home',
        ],
        'content' => [
            'path' => '/content',
            'controller' => 'content',
            'name' => 'Content',
        ],
        'shows' => [
            'path' => '/shows',
            'controller' => 'shows',
            'name' => 'Shows',
        ],
        'serve' => [
            'path' => '/serve',
            'name' => 'Serve',
            'controller' => 'serve'
        ],
        'comments'=> [
            'path' => '/comments',
            'name' => 'Comments',
            'controller' => 'comments'
        ]
    ];
    private $renderer;
    public $nav = [];

    function __construct($renderer)
    {
        $this->renderer = $renderer;
        $uri = parse_url($_SERVER['REQUEST_URI'])['path'];
        $paths = explode('/', $uri);
        array_shift($paths);
        $path = array_shift($paths);
        if (array_key_exists($path, $this->routes)) {
            $this->routes[$path]['active'] = true;
            $this->nav = ['endpoints'=>[]];
            foreach ($this->routes AS $route) {
                $this->nav['endpoints'][] = $route;
            }
            require base_path('controllers/' . $this->routes[$path]['controller'] . '.php');
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

    function get_nav()
    {
        return $this->nav;
    }
}