<?php

/**
 * Undocumented function
 *
 * @param array $routes
 * @param array $destinations
 * @param string $controller
 * @param string $action
 * @return mixed
 */
function router(
    array $routes,
    array $destinations,
    string $controller = 'index',
    string $action = 'indexAction'
) {
    // Check the type of interface between web server and PHP to see if it is a "cli-server".
    if (php_sapi_name() == 'cli-server') {
        $uri = substr($_SERVER["REQUEST_URI"], 1);
        $url = $uri === '' ? [$controller] : explode('/', filter_var(rtrim($uri, '/'), FILTER_SANITIZE_URL));
    } else {
        $url = isset($_GET['url'])
            ? explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL))
            : [$controller];
    }

    foreach ($routes as $key => $value) {
        if ($url[0] == $key) {
            if (!isset($url[1]) && array_key_exists('action', $value)) {
                $action = $value['action'] . 'Action';
            }

            $url[0] = $value['controller'];
        }
    }

    $controller = file_search("{$url[0]}.controller.php", $destinations);

    redirect_else($controller, route('/page-not-found'));

    // Remove '0' from $url array.
    unset($url[0]);

    // Require controller.
    require_once $controller;

    if (isset($url[1])) {
        redirect_else(function_exists("{$url[1]}Action"), route('/page-not-found'));

        $action = "{$url[1]}Action";

        // Remove '1' from $url array.
        unset($url[1]);
    }

    $params = $url ? array_values($url) : [];
    print_r($params);

    return call_user_func($action, $params);
}
