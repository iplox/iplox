<?php

namespace Iplox\Mvc {
    use Iplox\Mvc\Controller;
    
    class AppController extends Controller{

        public static $controllerNamespace = 'App\Controllers';
        public static $errorHandler = 'errorHandler';
        public static $defaultController = 'App';
        public static $defaultMethod = 'index';
        protected static $singleton;
        
        protected static $assumeRequestMethod = 'GET';
        protected static $pluralResourceToControler = true;
        
        //Retorna (e instancia si ya no lo está) el singleton del ApiController
        public static function getSingleton(){
            if(! isset(static::$singleton)){
                static::$singleton = new static();
            }
            return static::$singleton;
        }
        
        public static function init($req = null, $autoRun = true) {
            $inst = static::getSingleton();
            $r = $inst->router;
            $namespace = static::$controllerNamespace;
            $r->addFilters(array(
                ':controller' => function($name) use(&$r, &$namespace) {
                    if(!isset(static::$pluralResourceToControler)){
                        $class = $namespace . '\\' . ucwords($name) . 'Controller';
                        if (class_exists($class, TRUE)){
                            return true;
                        }
                    }
                    else {
                        //Con esto se aceptarán los plurales de los controladores.
                        //Ej: Si se solicita providers se verificaría si existe ProviderController.
                        $class = $namespace . '\\' . ucwords(preg_replace('/s$/', '', $name)) . 'Controller';
                        if (class_exists($class, TRUE)) {
                            return true;
                        } 
                    }
                    
                    return false;
                    
                }
            ));
            
            $r->addRoutes([
                '/:controller/:var' => array(static::$singleton, 'capture'),
                '/:controller' => array(static::$singleton, 'capture'),
                '/' => array(static::$singleton, 'capture')
            ]);
            
            if($autoRun){
                if(!isset($req)) $req = $_SERVER['REQUEST_URI'];
                $r->check($req);
            }
        }
        
        public function capture($controllerName = null, $methodName = null, $path = null) {
            if (isset(static::$pluralResourceToControler) && isset($controllerName)){
                $controllerName = static::$controllerNamespace . '\\' . ucwords(preg_replace('/s$/', '', $controllerName)) . "Controller";
            }
            else if (isset($controllerName)){
                $controllerName = static::$controllerNamespace . '\\' . $controllerName . "Controller";
            }
            else {
                $controllerName = static::$defaultController;
            }
            
            if(isset($methodName)){
                $method = $methodName;
            }
            else {
                $method = static::$defaultMethod;
            }
            
            $obj = new $controllerName();
            //El controlador y metodo existen?
            if (method_exists($obj, $method.'_'.static::$singleton->router->requestMethod)) {
                call_user_func(array($obj, $method.'_'.static::$singleton->router->requestMethod));
            } 
            //Se el requestMethod solicitado se corresponde con el method a asumir en los metodos del objeto, 
            //se verifica si el metodo existe si en requestMethod como sufijo.
            else if (static::$singleton->router->requestMethod === static::$assumeRequestMethod && method_exists($obj, $method)) {
                call_user_func(array($obj, $method));
            } 
            //El metodo de captura ante errores existe en el objeto?
            else if (method_exists($obj, static::$errorHandler)) {
                call_user_func(array($obj, static::$errorHandler));
            } 
            
            else {
                call_user_func(array(static::$singleton, static::$errorHandler));
            }
        }
        
        public function errorHandler($req = '/') {
            echo("El recurso <<".static::$singleton->router->request.">> no se pudo encontrar en este aplicación.");
        }
    }

}

?>
