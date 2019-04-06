<?php

/**
 * Router.
 *
 * @param array $routes
 * @param array $destinations
 * @param array $middleware
 * @return mixed
 */
function _router(
    array $routes,
    array $destinations,
    array $middleware = []
) {

    foreach ($middleware['web']['beforeMiddleware'] as $before_middlware) {
        require_once $before_middlware;
    }

    $action = 'indexAction';
    $controller = 'index';
    $route_middleware_group = [];
    $returns_view = [false, 'view' => ''];

    // Check the type of interface between web server and PHP to see if it is a "cli-server".
    if (php_sapi_name() == 'cli-server') {
        $uri = substr($_SERVER["REQUEST_URI"], 1);
        $url = $uri === '' ? [$controller] : explode('/', filter_var(rtrim($uri, '/'), FILTER_SANITIZE_URL));
    } else {
        $url = isset($_GET['url'])
            ? explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL))
            : [$controller];
    }

    foreach ($routes['web'] as $key => $value) {
        $returns_view[0] = is_string($value);
        $returns_view['view'] = $value;

        if ($url[0] == $key) {

            if ($returns_view[0]) {
                break;
            }

            if (!isset($url[1]) && array_key_exists('action', $value)) {
                $action = $value['action'] . 'Action';
            }

            $url[0] = $value['controller'];

            $route_middleware_group = array_key_exists('middleware', $value) ? $value['middleware'] : [];
            
            break;
        }
    }
    
    if (array_key_exists('before', $route_middleware_group)) {
        foreach ($route_middleware_group['before'] as $route_middlware) {
            require_once $route_middlware;
        }
    }

    if ($returns_view[0]) {

        _view($returns_view['view']);
        
    } else {
        $controller = _file_search("{$url[0]}.controller.php", $destinations);

        _redirect_else($controller, _route('/page-not-found'));
    
        // Remove '0' from $url array.
        unset($url[0]);
    
        // Require controller.
        require_once $controller;
    
        if (isset($url[1])) {
            _redirect_else(function_exists("{$url[1]}Action"), _route('/page-not-found'));
    
            $action = "{$url[1]}Action";
    
            // Remove '1' from $url array.
            unset($url[1]);
        }
    
        $params = $url ? array_values($url) : [];
    
        call_user_func($action, $params);
    }
    
    if (array_key_exists('after', $route_middleware_group)) {
        foreach ($route_middleware_group['after'] as $route_middlware) {
            require_once $route_middlware;
        }
    }

    foreach ($middleware['web']['afterMiddleware'] as $after_middlware) {
        require_once $after_middlware;
    }
}
