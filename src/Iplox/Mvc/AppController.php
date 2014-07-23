<?php

    namespace Iplox\Mvc;
    use Iplox\Mvc\Controller;
    
    class AppController extends Controller
    {
        public static $controllerNamespace = 'App\Controllers';
        public static $errorHandler = 'errorHandler';
        public static $defaultController = 'App';
        public static $defaultMethod = 'index';
        public static $defaultMethodPosfix = 'Action';
        public static $classPosfix = 'Controller';
        
        protected static $singleton;
        
        protected static $assumeRequestMethod = 'GET';
        protected static $pluralResourceToControler = true;
        
        //Retorna (e instancia si ya no lo está) el singleton del ApiController
        public static function getSingleton()
        {
            if(! isset(static::$singleton))
            {
                static::$singleton = new static();
            }
            return static::$singleton;
        }
        
        public static function init($req = null, $autoRun = true)
        {
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
                },
                ':num'=> function($val){
                    if(is_numeric($val)){
                        return true;
                    }
                    else {
                        return false;
                    }
                }
            ));
            
            //Determine if any of this routes actually exists as an object
            $r->addRoutes([
                '/:namespace/:controller/:method/*params'=>  array(static::$singleton, 'captureNSControllerMethod'),
                '/:controller/:method/*params'=>  array(static::$singleton, 'captureControllerMethod'),
                '/:method/*param' => array(static::$singleton, 'captureMethod'),
                '/*param' => array(static::$singleton, 'captureAll'),
                '/' => array(static::$singleton, 'capture')
            ]);
            
            if($autoRun){
                if(!isset($req)) {
                    $req = $_SERVER['REQUEST_URI'];
                }
                $r->check($req);
            }
        }
        
        public function captureNSControllerMethod($ns, $controller, $method, $params=array())
        {
            $controllerName =
                static::$controllerNamespace . '\\' .
                (isset($ns) ? ucwords($ns) . '\\' : '') .
                ucwords($controller) . static::$classPosfix;
                
            if(class_exists($controllerName)){
                $inst = new $controllerName();
                if(method_exists($inst, $method . ucwords(static::$singleton->router->requestMethod))){    
                    call_user_func_array(array($inst, $method . ucwords(static::$singleton->router->requestMethod)), $params);
                }
                else if(method_exists($inst, $method . ucwords(static::$defaultMethodPosfix))){
                    call_user_func_array(array($inst, $method . ucwords(static::$defaultMethodPosfix)), preg_split('/\/{1}/', $params));  
                }
                else {
                    return false;
                }
                return true;    
                
            }
            return false;
        }
        
        public function captureNSController($ns, $controller)
        {
            return $this->captureNSControllerMethod(
                $ns,
                $controller,
                static::$defaultMethod
            );
        }
        
        public function captureControllerMethod($controller, $method, $params=''){
            return $this->captureNSControllerMethod(
                null,
                $controller,
                $method,
                $params
            );
        }
        
        public function captureMethod($method, $params='')
        {
            return $this->captureNSControllerMethod(
                null,
                static::$defaultController,
                $method,
                $params
            );
        }
        
        public function captureAll($params='')
        {
            return $this->captureNSControllerMethod(
                null,
                static::$defaultController,
                static::$defaultMethod,
                $params
            );
        }
                
        public function errorHandler($req = '/')
        {
            throw Exception("El recurso <<".static::$singleton->router->request.">> no se pudo encontrar en este aplicación.");
        }
    }