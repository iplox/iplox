<?php

    namespace Iplox\Mvc;
    use Iplox\Mvc\Controller;
    use Iplox\Config as Cfg;
    use Iplox\Bundle;
    
    class AppController extends Controller
    {
        public static $controllerNamespace = 'App\Controllers';
        public static $methodPosfix = 'Action';
        public static $classPosfix = 'Controller';
        public static $errorHandler = 'errorHandler';

        // En los casos donde el $req='/', $defaultController y $defaultMethod se usarán para resolver el request.
        public static $defaultController = null;
        public static $defaultMethod = null;

        // En caso de que se haga un request que no pueda ser resuelto $catchAllController $catchAllMethod se usaran para resolver el request.
        public static $catchAllController = null;
        public static $catchAllMethod = null;

        // La unica instancia que tendrá esta clase.
        protected static $singleton;
        
        protected static $requestMethod = 'GET';
        protected static $pluralResourceToControler = true;
        
        //Retorna (e instancia si aún no lo está) el singleton
        public static function getSingleton()
        {
            if(! isset(static::$singleton))
            {
                static::$singleton = new static();
            }
            return static::$singleton;
        }
        
        public static function init($req = '/', $autoRun = true, $appDir = null)
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
            }
            else {
                Cfg::setEnv($_IPLOXENV);   
            }
                      
            //Config of the Application Dir.
            if(isset($appDir)){
                if(is_readable($appDir)){
                    Cfg::setAppDir($appDir);
                }
                else {
                    throw Exception('El directorio especificado como appDir no existe o tiene acceso restringido.');
                }
            }
            else {
                Cfg::setAppDir(dirname($rc->getFileName())); 
            }
            
            //Config of the Application Namespace
            Cfg::setAppNamespace($rc->getNamespaceName()); 
            
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
                if(!isset($req)) {
                    $req = $_SERVER['REQUEST_URI'];
                }
                $r->check($req);
            }
        }
        
        public function captureNSControllerMethod($ns, $controller, $method, $params='') {
            $controllerName =
                static::$controllerNamespace . '\\' .
                (isset($ns) ? ucwords($ns) . '\\' : '') .
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
        
        public function captureNSController($ns, $controller)
        {
            return $this->captureNSControllerMethod(
                $ns,
                $controller,
                static::$defaultMethod
            );
        }
        
        public function captureControllerMethod($controller, $method, $params='')
        {
            return $this->captureNSControllerMethod(
                null,
                $controller,
                $method,
                $params
            );
        }
        
        public function captureController($controller, $params='')
        {
            return $this->captureNSControllerMethod(
                null,
                $controller,
                static::$defaultMethod,
                $params
            );
        }

        public function captureDefault() {
            if(static::$defaultController && static::$defaultMethod){
                return $this->captureNSControllerMethod(
                    null,
                    static::$defaultController,
                    static::$defaultMethod,
                    ''
                );
            } else {
                throw new \Exception("No se especificó un controlador ni un metodo por defecto.");
            }
        }

        public function captureAll($params='') {
            if(empty($params)) {
                return $this->captureDefault();
            } else if(static::$catchAllController && static::$catchAllMethod) {
                return $this->captureNSControllerMethod(
                    null,
                    static::$catchAllController,
                    static::$catchAllMethod,
                    $params
                );
            } else {
                throw new \Exception("No se especificó ni un contrador ni un metodo de captura universal.");
                return false;
            }
        }

        public function errorHandler($req = '/') {
            throw \Exception("El recurso <<".static::$singleton->router->request .
                ">> no se pudo encontrar en este aplicación.");
        }
    }