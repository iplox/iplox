<?php

namespace Iplox;

class Router {
    protected $routes;

    protected $route;
    protected $regexRoute;
    protected $request;
    protected $requestMethod;
    protected $filters;

    function __construct() {
        $this->resetRoutes();
        $this->filters = array();
        $this->goHandlers = array();
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

        //
        if(in_array($method, $this->routes)){
            throw new Exception('Not valid methodName was specified. Expected one of these: GET, POST, PUT, DELETE, ALL');
        } else if($method !== 'ALL') {
            $routeList = array_merge($this->routes[$method], $this->routes['ALL']);
        } else {
            $routeList = $this->routes[$method];
        }

        //
        foreach($routeList as $endpoint => $callback) {
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
                    call_user_func_array($callback, $matches);
                    return true;
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
        $regexRoute = '';
        $pathSeparator = '\/';
        $resolvedPath = '';
        $routeSections = preg_split('/\//', preg_replace('/^\//', '', $route));
        $pathSections = preg_split('/\//', preg_replace('/^\//', '', $req));

        foreach($routeSections as $i => $rs) {
            // Si $rs esta registrado como filtro, se verifica si el valor pasado es válido.
            if(array_key_exists($rs, $this->filters) &&
                array_key_exists($i, $pathSections) &&
                !$this->checkFilter($rs, [$pathSections[$i], $resolvedPath])){
                return false;
            }

            //Match when found :value pattern
            else if(preg_match('/:\w*/', $rs) > 0){
                $regexRoute .= preg_replace(
                    ['/:\w*/', '/\)\?/', '/\)\)/', '/\(\(/'],
                    ['('.$pathSeparator.'[^\/]+'.')', ')?', ')', '('],
                    $rs);
            }
            //Match when found *value pattern
            else if(preg_match('/\*\w*/', $rs) > 0){
                $regexRoute .= preg_replace(
                    ['/\*\w*/', '/\)\?/','/\(\(/', '/\)\)/'],
                    ['(.+)', ')?','(',')'],
                    $rs);
            }
            //Match when not found any of the above pattern put the curresponding string in the $pathSections array, if there is one.
            else {
                $regexRoute .= preg_replace(
                    ['/\w+/', '/\)\?/', '/\(\(/', '/\)\)/'],
                    [$pathSeparator.$rs, ')?', '(', ')'],
                    $rs);
            }
            $pathSeparator = '\/';
        }
        //The actual regular expression generated.
        $regexRoute = '/^'.$regexRoute.'/';

        // Se verifica si $req coincide con el $regexRoute construído a partir de la ruta ($routeSections)
        if(preg_match($regexRoute, $req, $matches) > 0) {
            //This remove the first element matched which is not required.
            array_shift($matches);

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
    public function appendRoutes($routes=array(), $method="ALL") {
        if(array_key_exists($method, $this->routes)) {
            $this->routes[$method] = array_merge($this->routes[$method], $routes);
        }
    }

    //Agrega rutas al inicio del arreglo de rutas.
    public function prependRoutes($routes=array(), $method="ALL") {
        if(array_key_exists($method, $this->routes)) {
            $tmpArray = array();
            foreach($routes as $k=> $v) {
                if(array_key_exists($k, $this->routes[$method])) {
                    $this->routes[$method] = $v;
                } else {
                    $tmpArray[$k] = $v;
                }
                $this->routes[$method] = array_merge($tmpArray, $this->routes[$method]);
            }
        }
    }

    public function getRoutes() {
        return $this->routes;
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
        $resetValues =  [
            'GET'=>[],
            'POST'=>[],
            'DELETE'=>[],
            'PUT'=>[],
            'ALL'=>[]
        ];
        if(!is_null($method) && array_key_exists(strtoupper($method), $resetValues)) {
            $this->routes[strtoupper($method)] = [];
        } else {
            $this->routes = $resetValues;
        }
    }
}
