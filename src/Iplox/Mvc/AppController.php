<?php

    namespace Iplox\Mvc;
    use Iplox\Mvc\Controller;
    use Iplox\Config as Cfg;
    use Iplox\Bundle;
    
    class AppController extends Controller
    {
        public static $appNamespace = 'App';
        public static $controllerNamespace = 'Controllers';
        public static $methodPosfix = 'Action';
        public static $defaultMethod = 'index';
        public static $classPosfix = 'Controller';
        public static $errorHandler = 'errorHandler';

        // Metodos de captura por defecto ($req="/") y global para cuando no haya metodo que coincida.
        public static $catchDefaultHandler = ['static', 'defaultHandler'];
        public static $catchAllHandler =  ['static', 'globalHandler'];


        // La unica instancia que tendrá esta clase.
        protected static $singleton;
        
        protected static $requestMethod = 'GET';
        protected static $pluralResourceToControler = true;

        public $router = null;

        //Retorna una instancia de la clase: el singleton
        public static function getSingleton() {
            if(! isset(static::$singleton))
            {
                static::$singleton = new static();
            }
            return static::$singleton;
        }
        
        public static function init($appDir = null, $autoRun = true, $req = null) {
            $inst = static::getSingleton();
            $r = $inst->router;

            // Filters for the MVC Controller functionality
            $r->addFilters(array(
                ':controller' => function($name, $path) use(&$r, &$namespace) {
                    $ns = static::$appNamespace . '\\' .
                        ucwords(preg_replace(['/^\//', '/\//'], ['', '\\'], $path)) .
                        static::$controllerNamespace . '\\';
                    $class = $ns . ucwords($name) . 'Controller';
                    if (class_exists($class, TRUE)){
                        return true;
                    }
                    return false;
                },
                ':num'=> function($val){
                    if(is_numeric($val)){
                        return true;
                    } else {
                        return false;
                    }
                }
            ));
            
            //Determine if any of this routes actually exists as an object
            $r->appendRoutes([
                '/:namespace/:controller/:method/*params'=>  array(static::$singleton, 'captureNSControllerMethod'),
                '/:namespace/:controller/*params'=>  array(static::$singleton, 'captureNSController'),
                '/:controller/:method/*params'=>  array(static::$singleton, 'captureControllerMethod'),
                '/:controller/*param' => array(static::$singleton, 'captureController'),
                '/*param' => array(static::$singleton, 'captureAll')
            ]);
            
            
            /**** Configurations ****/
            //Reflection Class
            $rc = new \ReflectionClass(\get_called_class());
                
            //Config of the Enviroment
            if(!isset($_IPLOXENV)) {
                Cfg::setEnv('DEVELOPMENT');                
            } else {
                Cfg::setEnv($_IPLOXENV);   
            }
                      
            //Config of the Application Dir.
            if(isset($appDir)){
                if(is_readable($appDir)){
                    Cfg::setAppDir($appDir);
                } else {
                    throw \Exception('El directorio especificado como appDir no existe o tiene acceso restringido.');
                }
            } else {
                Cfg::setAppDir(dirname($rc->getFileName())); 
            }

            //Config of the Application Namespace
            if(isset(static::$appNamespace)){
                Cfg::setAppNamespace(static::$appNamespace);
            } else {
                Cfg::setAppNamespace('');
            }

            //Setup of all bundles.
            $gral = Cfg::get('Bundles');
            $rc = new \ReflectionClass($gral);
            $bundles = $rc->getConstants();
            foreach($bundles as $name => $value){
                $bdl = Bundle::get(ucwords($name));
                if(method_exists($bdl, 'setup')){
                    call_user_func(array($bdl, 'setup'));
                }
            }

            //Time to run
            if($autoRun){
                $inst->run($req);
            }

            return $inst;
        }

        // Run the app by matching requeset to routes and handling methods.
        public function run($req = null){
            $r = static::getSingleton()->router;
            if(!isset($req)) {
                $req = preg_replace('/\?(.*\=.*)*$/', '', $_SERVER['REQUEST_URI']) ;
                $req = empty($req) ? '/' : $req;
            }
            $r->check($req);
        }
        
        public function captureNSControllerMethod($ns, $controller, $method, $params='') {
            $controllerName =
                static::$appNamespace . '\\' .
                (isset($ns) ? ucwords($ns) . '\\' : '') .
                static::$controllerNamespace . '\\' .
                ucwords($controller) . static::$classPosfix;

            if(class_exists($controllerName)) {
                $inst = new $controllerName();
                if(method_exists($inst, $method . ucwords(static::$singleton->router->requestMethod))) {
                    call_user_func_array(array($inst, $method . ucwords(static::$singleton->router->requestMethod)), $params);
                } else if(method_exists($inst, $method . ucwords(static::$methodPosfix))){
                    call_user_func_array(array($inst, $method . ucwords(static::$methodPosfix)), preg_split('/\/{1}/', $params));
                } else {
                    throw new \Exception("El método \"$method" .
                        ucwords(static::$singleton->router->requestMethod)."\" or \"" .
                        $method . ucwords(static::$methodPosfix)."\" no fue encontrado en el controlador $controllerName.");
                    return false;
                }
                return true;
            } else {
                throw new \Exception("El controlador \"$controllerName\" no fue encontrado.");
            }
            return false;
        }
        
        public function captureNSController($ns, $controller) {
            return $this->captureNSControllerMethod(
                $ns,
                $controller,
                static::$defaultMethod
            );
        }
        
        public function captureControllerMethod($controller, $method, $params='') {
            return $this->captureNSControllerMethod(
                null,
                $controller,
                $method,
                $params
            );
        }
        
        public function captureController($controller, $params='') {
            return $this->captureNSControllerMethod(
                null,
                $controller,
                static::$defaultMethod,
                $params
            );
        }

        public function captureAll($params='') {
            if(empty($params)) {
                if (is_callable(static::$catchDefaultHandler)) {
                    return call_user_func(static::$catchDefaultHandler);
                }
                throw new \Exception("A \"catchDefaultHandler\" was not provided or is not valid.");
            } else {
                if (is_callable(static::$catchAllHandler)) {
                    return call_user_func(static::$catchAllHandler, $params);
                }
                throw new \Exception("A \"catchAllHandler\" was not provided or is not valid.");
            }
            return false;
        }

        public static function globalHandler ($param) {
            echo "<h2>No existe el recurso solicitado</h2>Este es el metodo de captura global por defecto.";
        }

        public static function defaultHandler() {
            echo "<h2>Welcome</h2>Este es el metodo de captura por defecto.";
        }
    }