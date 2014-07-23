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
            //
            if(!isset($req))
            {
                $req = $_SERVER['REQUEST_URI'];
            }
            //
            if(!isset($method))
            {
                $method = $_SERVER['REQUEST_METHOD'];
            }
            //
            $req = preg_replace('/\/$/', '', $req);
            
            //
            if($method !== 'ALL')
            {
                $routeList = array_merge($this->routes[$method], $this->routes['ALL']);
            }
            else {
                $routeList = $this->routes[$method];
            }
            
            //
            foreach($routeList as $endpoint => $callback)
            {
                $routeSections = preg_split('/\/{1}/', $endpoint);
                array_shift($routeSections);
                if(count($routeSections)==1 && empty($routeSections[0])) $routeSections= [];
                
                $pathSections = preg_split('/\/{1}/', $req);
                array_shift($pathSections);
                                    
                if(count($routeSections) > count($pathSections))
                { 
                    continue;
                }
                else
                {
                    //Esta es la ruta?
                    $matches = $this->checkRoute($routeSections, $pathSections, $req);
                    if(is_array($matches)){
                        //Si lo es. Se hace lo que se vaya a hacer.
                        //Se asignan los valores de la ruta, verbo y de la solicitud.
                        $this->request = $req;
                        $this->route = $endpoint;
                        $this->requestMethod = $method;
                        
                        //Se resetean las rutas. Para que se puedan agregar nuevas si así  se desea.
                        $this->routes[$method] = array();
    
                        if(is_callable($callback))
                        {
                            //Se llama a la función de callback y se pasan los parámetros de la url solicitada
                            if(call_user_func_array($callback, $matches))
                            {
                                 return true; 
                            }
                        }
                    }
                    continue; //No lo es. Continua con los otros endpoints.
                }
                    
            }
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
        
        public function checkRoute($routeSections, $pathSections, $req)
        {
            $regexRoute; $matches=array();
            $regexRoute = ''; 
            foreach($routeSections as $rs)
            {
                if(preg_match('/^\*\w*/', $rs) === 1)
                {
                    $regexRoute .= '(.*)';
                }
                else if(preg_match('/^:\w*/', $rs) === 1)
                {
                    $regexRoute .= '([\w]*)';
                }
                $regexRoute = $regexRoute.'\/';
            }
            $req =  preg_replace('/^\//', '', preg_replace('/\/$/', '', $req));
            $regexRoute = '/^'.substr($regexRoute, 0, count($regexRoute)-3).'/';
            
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