<?php

namespace Iplox;

class Router {
    public $routes;
    
    protected $route;
    protected $regexRoute;
    protected $request;
    protected $requestMethod;
    protected $filters;
    
    function __construct()
    {
        $this->routes = array
        (
            'GET'=> array(),
            'POST'=> array(),
            'PUT'=> array(),
            'DELETE'=> array(),
            'ALL'=> array()
        );
        $this->filters = array();
        
        $this->goHandlers = array();
        
    }

    /**** Routes ****/

    public function check($req = null, $method=null) {
        if(isset($this->route)){
            $req = $req ? $req : preg_replace($this->regexRoute, '', $this->request);
            $method = $method ? $method : $this->requestMethod;
        }
        else {
            $req = $req ? preg_replace('/\/$/', '', $req) : $_SERVER['REQUEST_URI'];
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
            $routeSections = preg_split('/\/{1}/', $endpoint);
            array_shift($routeSections);
            if(count($routeSections)==1 && empty($routeSections[0])) {
                $routeSections= [];
            }

            $pathSections = preg_split('/\/{1}/', $req);
            array_shift($pathSections);

            //El catchAll (*) filter type allow to match 0 path section as 1 or even more. For that reason the -1 operation.
            if((count($routeSections) - 1) > count($pathSections)) {
                continue;
            } else {
                //Esta es la ruta?
                $matches = $this->checkRoute($routeSections, $pathSections, $req);
                if(is_array($matches)) {
                    //Si, lo es. Ahora se le asignaran los valores de la ruta, verbo y de la solicitud.
                    $this->request = $req;
                    $this->route = $endpoint;
                    $this->requestMethod = $method;

                    //Se resetean las rutas. Para que se puedan agregar nuevas si así se desea.
                    $this->routes[$method] = array();

                    if(is_callable($callback)) {
                        //Se llama a la función de callback y se pasan los parámetros de la url solicitada
                        call_user_func_array($callback, $matches);
                        return true;
                    } else {
                        throw new \Exception("Not valid callable entity was provided as handler to the route $req.");
                    }
                } else {
                    //No, lo es. Continua con los otros endpoints.
                    continue;
                }
            }
        }
        return false;
    }

    public function checkRoute($routeSections, $pathSections, $req) {
        $matches = array();
        $regexRoute = '';
        $pathSeparator = '';
        $resolvedPath = '';
        foreach($routeSections as $i => $rs) {
            // Si $rs esta registrado como filtro, se verifica si el valor pasado es válido.
            if(array_key_exists($rs, $this->filters) &&
                array_key_exists($i, $pathSections) &&
                !$this->checkFilter($rs, [$pathSections[$i], $resolvedPath])){
                return false;
            }

            // Cada $rs se transformará en un RegexExp que se integrará al regexRoute final que determinará si el route es el correcto.
            if(preg_match('/^\*\w*/', $rs) === 1) {
                $regexRoute .= '('.$pathSeparator.'.*)?';
            }
            else if(preg_match('/^:\w*/', $rs) === 1) {
                $regexRoute .= $pathSeparator.'([\w]*)';
            } else {
                $regexRoute .= $pathSeparator.$rs;
            }
            if(array_key_exists($i, $pathSections)){
                $resolvedPath .= $pathSections[$i].'/';
            }
            $pathSeparator = '\/';
        }
        $regexRoute = '/^\/?'.$regexRoute.'/';

        // Se verifica si $req coincide con el $regexRoute construído a partir de la ruta ($routeSections)
        if(preg_match($regexRoute, $req, $matches) > 0) {
            array_shift($matches);
            if(isset($matches[0]) && $matches[0] == '') {
                array_shift($matches);
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

    /*****  HTTP Method Verification *****/

    //Devuelve true si el método solicitado es GET
    public function isGet() {
        if($_SERVER['REQUEST_METHOD'] === 'GET') {
            return true;
        } else {
            return false;
        }
    }
    
    //Devuelve true si el metodo solicitado es POST
    public function isPost() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            return true;
        } else {
            return false;
        }
    }
    
    //Devuelve tru si el metodo solicitado es PUT
    public function isPut() {
        if($_SERVER['REQUEST_METHOD'] === 'PUT') {
            return true;
        } else {
            return false;
        }
    }
    
    //Devuelve tru si el metodo solicitado es DELETE
    public function isDelete() {
        if($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            return true;
        } else {
            return false;
        }
    }       
}