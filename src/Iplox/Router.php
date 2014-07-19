<?php

namespace Iplox {

    class Router {
        public $routes;
        
        protected $route;
        protected $request;
        protected $requestMethod;
        protected $filters;
        
        function __construct(){
            $this->routes = array(
                'GET'=> array(),
                'POST'=> array(),
                'PUT'=> array(),
                'DELETE'=> array(),
                'ALL'=> array()
            );
            $this->filters = array();
            
            $this->goHandlers = array();
            
        }
        
        //Agrega nuevas rutas a resolver para un método en particular (GET, POST, PUT o DELETE).
        public function addRoutes($routes=array(), $method="ALL"){
            if(array_key_exists($method, $this->routes)){
                $this->routes[$method] = array_merge($this->routes[$method], $routes);
               
                uksort($this->routes[$method], function($a, $b){
                   return (strlen($a) < strlen($b)) ? true : false;
                }); 
            }
        }
        
        public function prependRoutes($routes=array(), $method="ALL"){
            if(array_key_exists($method, $this->routes)){
                
                $tmpArray = array();
                foreach($routes as $k=> $v){
                    if(array_key_exists($k, $this->routes[$method])) {
                        $this->routes[$method] = $v;
                    }
                    else {
                        $tmpArray[$k] = $v; 
                    }
                    $this->routes[$method] = array_merge($tmpArray, $this->routes[$method]);
                }
            }
        }
        
        public function getRoutes(){
            return $this->routes;
        }
        
        public function addFilter($filterName, $filterHandler){
            $this->filters["$filterName"] = $filterHandler;
        }
        
        public function addFilters($filters=array()){
            foreach($filters as $filter => $handler){
                $this->addFilter($filter, $handler);
            }
        }
        
        public function isFilter($filter){
            return array_key_exists($filter, $this->filters);
        }
        
        public function checkFilter($filter, $passedVal){
            foreach($this->filters as $k=> $v){
                if($k === $filter){
                    return call_user_func($v, $passedVal);
                }
            }
            return false;
        }
        
        public function check($req = null, $method=null){
            if(!isset($req)) $req = $_SERVER['REQUEST_URI'];
            if(!isset($method)) $method = $_SERVER['REQUEST_METHOD'];
            $req = preg_replace('/\/$/', '', $req);
            
            if($method !== 'ALL') {
                $routeList = array_merge($this->routes[$method], $this->routes['ALL']);
            }
            else {
                $routeList = $this->routes[$method];
            }
            
            foreach($routeList as $endpoint => $callback){
                $routeSections = preg_split('/\/{1}/', $endpoint);
                array_shift($routeSections);
                if(count($routeSections)==1 && empty($routeSections[0])) $routeSections= [];
//                if(count($routeSections)===0) $routeSections = [''];
                
                $pathSections = preg_split('/\/{1}/', $req);
                array_shift($pathSections);
                                    
                if(count($routeSections) > count($pathSections)) { 
                    continue;
                }
                else {
                    //Esta es la ruta?
                    $matches = $this->checkRoute($routeSections, $pathSections);
                    if(is_array($matches)){
                        //Si lo es. Se hace lo que se vaya a hacer.
                        //Se asignan los valores de la ruta, verbo y de la solicitud.
                        $this->request = $req;
                        $this->route = $endpoint;
                        $this->requestMethod = $method;

                        //Se resetean las rutas. Para que se puedan agregar nuevas si así  se desea.
                        $this->routes[$method] = array();

                        if(is_callable($callback)){
                           //Se llama a la función de callback y se pasan los parámetros de la url solicitada
                           return call_user_func_array($callback, $matches);
                        }
                        else { 
                           return false;
                        }
                    }
                    else {
                        continue; //No lo es. Continua con los otros endpoints.
                    }
                }
                    
            }
        }
        
        protected function checkRoute($routeSections, $pathSections){
            $pathValues = array();
            foreach($routeSections as $k => $rsec){
                //Se verifica si empieza con : o *.
                if(preg_match('/^[:\*][a-zA-Z\-_]*$/', $rsec)>0){
                    //Si no es un filtro registrado se toman los parámetros.
                    //Si el filtro está registrado, se evalúa.
                    if(! $this->isFilter($rsec) || $this->checkFilter($rsec, $pathSections[$k])){
                        //:type filter
                        if(preg_match('/^:/', $rsec)>0){
                            array_push($pathValues, $pathSections[$k]);
                        }
                        //*type filter
                        else { 
                            array_push($pathValues, $pathSections[$k]);
                        }
                        continue;
                    }
                    else {
                        return false;
                    }
                }
//                else if(preg_match('/^[a-zA-Z\-_]*$/', $rsec)>0){
//                    continue;
//                }
                //Ya que no es un filtro se hace una verificacion normal.
                if($rsec=== $pathSections[$k]){
                    continue;
                }
                else {
                    return false;
                }
            }
            return $pathValues;
        }
        
        
        //Este método permite agregar nuevas rutas después de la selección de una ruta que la contenga.
        public function next(){
            $rg = "/^\/".preg_replace('/^\//', '', $this->route)."/";
            $r = preg_replace($rg, "", $this->request);
            $r = ($r === "") ? "/" : $r;
            $this->run($r, $this->requestMethod);
        }
                
        public function __get($name){
            if($name === 'route'){
                return $this->route;
            }
            else if($name === 'request'){
                return $this->request;
            }
            else if($name === 'requestMethod'){
                return $this->requestMethod;
            }
        }
        
        //Devuelve tru si el metodo solicitado es GET
        public function isGet(){
            if($_SERVER['REQUEST_METHOD'] === 'GET'){
                return true;
            }
            else {
                return false;
            }
        }
        
        //Devuelve tru si el metodo solicitado es POST
        public function isPost(){
            if($_SERVER['REQUEST_METHOD'] === 'POST'){
                return true;
            }
            else {
                return false;
            }
        }
        
        //Devuelve tru si el metodo solicitado es PUT
        public function isPut(){
            if($_SERVER['REQUEST_METHOD'] === 'PUT'){
                return true;
            }
            else {
                return false;
            }
        }
        
        //Devuelve tru si el metodo solicitado es DELETE
        public function isDelete(){
            if($_SERVER['REQUEST_METHOD'] === 'DELETE'){
                return true;
            }
            else {
                return false;
            }
        }
        	
    }
}
?>
