<?php

namespace Iplox;

class Router {
    public $routes;
    
    protected $route;
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

    public function check($req = null, $method=null)
    {
        $req = $req ? preg_replace('/\/$/', '', $req) : ['REQUEST_URI'];
        $method = $method ? $method : $_SERVER['REQUEST_METHOD'];

        //
        if($method !== 'ALL') {
            $routeList = array_merge($this->routes[$method], $this->routes['ALL']);
        } else {
            $routeList = $this->routes[$method];
        }

        //
        foreach($routeList as $endpoint => $callback)
        {
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
    
    
    /**** Routes ****/
    //Agrega nuevas rutas a resolver para un método en particular (GET, POST, PUT o DELETE).
    public function addRoutes($routes=array(), $method="ALL")
    {
        if(array_key_exists($method, $this->routes))
        {
            $this->routes[$method] = array_merge($this->routes[$method], $routes);
           
            uksort($this->routes[$method], function($a, $b){
               return (strlen($a) < strlen($b)) ? true : false;
            }); 
        }
    }
    
    //Agrega rutas al inicio del arreglo de rutas.
    public function prependRoutes($routes=array(), $method="ALL")
    {
        if(array_key_exists($method, $this->routes))
        {     
            $tmpArray = array();
            foreach($routes as $k=> $v)
            {
                if(array_key_exists($k, $this->routes[$method]))
                {
                    $this->routes[$method] = $v;
                }
                else {
                    $tmpArray[$k] = $v; 
                }
                $this->routes[$method] = array_merge($tmpArray, $this->routes[$method]);
            }
        }
    }
    
    public function getRoutes()
    {
        return $this->routes;
    }

    public function checkRoute($routeSections, $req)
    {
        $matches=array();
        $regexRoute = '';
        $pathSeparator = '';
        foreach($routeSections as $rs)
        {
            if(preg_match('/^\*\w*/', $rs) === 1)
            {
                $regexRoute .= '('.$pathSeparator.'.*)?';
            }
            else if(preg_match('/^:\w*/', $rs) === 1)
            {
                $regexRoute .= $pathSeparator.'([\w]*)';
            }
            else {
                $regexRoute .= $pathSeparator.$rs;
            }
            $pathSeparator = '\/';
        }
        $req =  preg_replace('/^\//', '', preg_replace('/\/$/', '', $req));
        $regexRoute = '/^'.$regexRoute.'/';
        $countMatches = preg_match($regexRoute, $req, $matches);
        if(count($matches) > 0)
        {
            array_shift($matches);
            if(isset($matches[0]) && $matches[0] == '')
            {
                array_shift($matches);
            }
            return $matches;
        }
        return false;
    }
    
    //Este método permite agregar nuevas rutas después de la selección de una ruta que la contenga.
    public function next()
    {
        $rg = "/^\/".preg_replace('/^\//', '', $this->route)."/";
        $r = preg_replace($rg, "", $this->request);
        $r = ($r === "") ? "/" : $r;
        $this->run($r, $this->requestMethod);
    }
    
    
    
    /**** Filters ****/
    
    public function addFilter($filterName, $filterHandler)
    {
        $this->filters["$filterName"] = $filterHandler;
    }
    
    public function addFilters($filters=array())
    {
        foreach($filters as $filter => $handler)
        {
            $this->addFilter($filter, $handler);
        }
    }
    
    public function isFilter($filter)
    {
        return array_key_exists($filter, $this->filters);
    }
    
    public function checkFilter($filter, $passedVal)
    {
        echo "$passedVal"."<br/>";
        foreach($this->filters as $k=> $v)
        {
            if($k === $filter)
            {
                return call_user_func($v, $passedVal);
            }
        }
        return false;
    }
            
            
    /**** Properties ****/
    
    public function __get($name)
    {
        if($name === 'route')
        {
            return $this->route;
        }
        else if($name === 'request')
        {
            return $this->request;
        }
        else if($name === 'requestMethod')
        {
            return $this->requestMethod;
        }
    }
    
    //Devuelve tru si el metodo solicitado es GET
    public function isGet()
    {
        if($_SERVER['REQUEST_METHOD'] === 'GET')
        {
            return true;
        }
        else {
            return false;
        }
    }
    
    //Devuelve tru si el metodo solicitado es POST
    public function isPost()
    {
        if($_SERVER['REQUEST_METHOD'] === 'POST')
        {
            return true;
        }
        else {
            return false;
        }
    }
    
    //Devuelve tru si el metodo solicitado es PUT
    public function isPut()
    {
        if($_SERVER['REQUEST_METHOD'] === 'PUT')
        {
            return true;
        }
        else {
            return false;
        }
    }
    
    //Devuelve tru si el metodo solicitado es DELETE
    public function isDelete()
    {
        if($_SERVER['REQUEST_METHOD'] === 'DELETE')
        {
            return true;
        }
        else
        {
            return false;
        }
    }       
}