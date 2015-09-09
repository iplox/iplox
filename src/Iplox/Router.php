<?php

namespace Iplox;

class Router {
    protected $routes;

    protected $route;
    protected $regexRoute;
    protected $request;
    protected $requestMethod;
    protected $filters;
    protected $routeCount;

    function __construct() {
        $this->routes = array();
        $this->filters = array();
        $this->goHandlers = array();
        $this->routeCount = 0;
    }

    /**** Routes ****/

    /**
     * Resolve the request and execute any handler method previewsly added.
     * Take the requestUri and verify each route expecting to find a match.
     * @param string $reqUri The Uri to be requested. default
     * @param string $method The HTTP verb [GET|POST|PUT|DELETE] to be requested.
     * @return bool Returns true if any route was matched, false if not.
     * @api
     */
    public function check($req = null, $method=null) {
        if(isset($this->route)){
            $req = $req ? $req : preg_replace($this->regexRoute, '', $this->request);
            $method = $method ? $method : $this->requestMethod;
        }
        else {
            $req = $req ? preg_replace(['/^\/*/', '/\/$/'], ['/', ''], $req) : $_SERVER['REQUEST_URI'];
            $method = $method ? $method : $_SERVER['REQUEST_METHOD'];
        }
        $req = empty($req) ? '/' : $req;

        $routeList = $this->getRoutes($method);

        //
        foreach($routeList as $r) {
            $endpoint = $r[0];
            $callback =  $r[1];

            //Esta es la ruta?
            $matches = $this->checkRoute($endpoint, $req);
            if(is_array($matches)) {
                //Si, lo es.

                if(is_callable($callback)) {
                    //Ahora se le asignaran los valores de la ruta, metodo y de la solicitud.
                    $this->request = $req;
                    $this->route = $endpoint;
                    $this->requestMethod = $method;
                    $this->regex = $this->regexRoute;
                    //Se resetean las rutas. Para que se puedan agregar nuevas si así se desea.
                    $this->resetRoutes($method);
                    //Se llama a la función de callback y se pasan los parámetros de la url solicitada
                    return call_user_func_array($callback, $matches);
                } else if(is_string($callback) || is_array($callback)) {
                    // What if $callback is a string? It means, it's a request.
                    if(is_string($callback)){
                        // Same method
                        $method = $this->requestMethod;
                        $req = $callback;
                    } else {
                        // Method is optionally specified in the request array.
                        $method = array_key_exists('method', $callback) ? $callback['method'] : $this->requestMethod;

                        if(array_key_exists('request', $callback) && is_string($callback['request'])) {
                            $req = $callback['request'];
                        } else {
                            throw new \Exception('The forwarding request was not specified or the type is invalid.');
                        }
                    }
                    $rg = [];
                    foreach($matches as $i => $v){
                        array_push($rg, '/\{\$'. ($i) . '\}/');
                    }

                    $req = preg_replace($rg, $matches, $req);
                    if(preg_match('/\{\$\d+\}/', $req) > 0){
                        throw new \Exception('Incorrect variable number in request redirect. The maximum value variable is {$'. (count($matches)-1).'}');
                    }
                    return $this->check($req, $method);
                } else {
                    throw new \Exception("Not valid callable entity was provided as handler to the route $req.");
                }
            } else {
                //No, lo es. Continua con los otros endpoints.
                continue;
            }
        }
        return false;
    }

    /**
     * Check a route against the requestUri.
     * Verify if a request match a provided route.
     * @param string $route The route to be verify.
     * @param string $requestUri the Uri to check against.
     * @return $mixed It returns an array of segment matches when the route match the request. It returns false if not.
     * @api
     */
    public function checkRoute($route, $req) {
        $matches = [];

        $rgxR = preg_replace(
            [
                '/^\/*/',
                '/\/+/',
                '/\{([\w\-]*)\}\?/',
                '/\{[\w\-]*\}/',
                '/(:\w*\?)|(\{:\w*\}\?)/',
                '/(:\w*)|(\{:\w*\})/',
                '/(\*\w*\?)|(\{\*\w*\}\?)/',
                '/(\*\w*)|(\{\*\w*\})/'
            ], [
            '',
            '\/',
            '?($1)?',
            '$1',
            '<segment_opt>',
            '<segment>',
            '<glob_opt>',
            '<glob>'
        ],
            $route
        );

        //The actual regular expression generated.
        $regexRoute = '/^\/' . str_replace([
                '<segment_opt>',
                '<segment>',
                '<glob_opt>',
                '<glob>',
            ], [
                '?(\/[\w\-]+)?',
                '([\w\-]+)',
                '?(\/[\w\-\/]+)?',
                '([\w\-\/]+)',
            ],
                $rgxR
            ) . '$/';

        $req = '/' . preg_replace(['/^\/*/', '/\/$/', '/\/+/'], ['', '', '/'], $req);

        // Check if the $req uri match the route.
        if(preg_match($regexRoute, $req, $matches) > 0) {
            //This remove the first element matched which is not required.
            array_shift($matches);

            preg_match('/:\w*/', $route, $segments);
            foreach($segments as $i => $segment){
                $segment = preg_replace('/^:/', '', $segment);
                // If $segment is registered as filter and its value is valid, then proceed
                if($this->isFilter($segment) &&
                    !$this->checkFilter($segment, [$matches[$i], $req])){
                    return false;
                }
            }

            //This remove the slash (/) from the beginning of all matched values.
            foreach($matches as $i=>$m){
                $matches[$i] = preg_replace('/^\//', '', $m);
            }

            // La regexRoute que coincidió con el request.
            $this->regexRoute = $regexRoute;
            return $matches;
        }
        return false;
    }

    //Agrega nuevas rutas a resolver para un método en particular (GET, POST, PUT o DELETE).
    public function appendRoutes($routes=array(), $method="any") {
        if($method !== 'any' and ! $this->isMethod($method)){
            throw new \Exception('Not valid $method was specified. Expected one of these: get, post, put, delete, any');
        }
        foreach($routes as $route => $handler){
            array_push($this->routes, array($route, $handler, $method));
        }
    }

    //Agrega rutas al inicio del arreglo de rutas.
    public function prependRoutes($routes=array(), $method="any") {
        if($method !== 'any' and ! $this->isMethod($method)){
            throw new \Exception('Not valid $method was specified. Expected one of these: get, post, put, delete, any');
        }
        foreach($routes as $route => $handler){
            array_unshift($this->routes, array($route, $handler, $method));
        }
    }

    public function prependRoute($route, $handler, $method = 'any')
    {
        if($method !== 'any' and ! $this->isMethod($method)){
            throw new \Exception('Not valid $method was specified. Expected one of these: get, post, put, delete, any');
        }
        array_unshift($this->routes, array($route, $handler, $method));
    }

    public function appendRoute($route, $handler, $method = 'any')
    {
        if($method !== 'any' and ! $this->isMethod($method)){
            throw new \Exception('Not valid $method was specified. Expected one of these: get, post, put, delete, any');
        }
        array_push($this->routes, array($route, $handler, $method));
    }

    public function getRoutes($method)
    {
        if(! $this->isMethod($method)){
            throw new \Exception('Not valid $method was specified. Expected one of these: get, post, put, delete, any');
        }
        $routes = [];
        foreach ($this->routes as $r) {
            if ($r[2] == strtolower($method) || $r[2] == 'any') {
                array_push($routes, $r);
            }
        }
        return $routes;
    }


    /**** Filters ****/

    public function addFilter($filterName, $filterHandler) {
        $this->filters["$filterName"] = $filterHandler;
    }

    public function addFilters($filters=array()) {
        foreach($filters as $filter => $handler) {
            $this->addFilter($filter, $handler);
        }
    }

    public function isFilter($filter) {
        return array_key_exists($filter, $this->filters);
    }

    public function checkFilter($filter, $args) {
        foreach($this->filters as $k=> $v) {
            if($k === $filter) {
                return call_user_func_array($v, $args);
            }
        }
        return false;
    }


    /**** Properties ****/

    public function __get($name) {
        if($name === 'route') {
            return $this->route;
        } else if($name === 'request') {
            return $this->request;
        } else if($name === 'requestMethod') {
            return $this->requestMethod;
        }
    }

    //Clear routes. If a method is provided clear only that specifics.
    public function resetRoutes($method = null) {
        $routes = [];
        if($this->isMethod($method)) {
            foreach ($this->routes as $r) {
                if ($r[2] !== strtolower($method)) {
                    array_push($routes, $r);
                }
            }
        }
        $this->routes = $routes;
    }

    public function isMethod($method)
    {
        return in_array(strtolower($method), ['get', 'post', 'update', 'delete']) ? true : false;
    }
}