<?php

namespace Iplox\Mvc {
    use Iplox\Mvc\Controller;
    
    class ApiController extends Controller{

        public static $controllerNamespace = 'App\Controllers';
        public static $errorHandler = 'errorHandler';
        public static $defaultController = 'App';
        public static $defaultMethod = 'index';
        protected static $singleton;
        
        protected static $assumeRequestMethod = 'GET';
        protected static $pluralResourceToControler = true;
        
        protected static $defaultOutputFormat = 'json';
        protected $outputHandlers;
        protected $outputFormat = true;
        
        public function __construct() {
            parent::__construct();
            $this->outputHandlers = array(
                'json'=>    array('Content-type'=>'application/json', 'handler'=> array($this, 'to_json')),
                'jsonp'=>   array('Content-type'=>'application/javascript', 'handler'=> array($this, 'to_jsonp')),
                'array'=>   array('handler'=> array($this, 'to_array')),
                'text'=>    array('Content-type'=>'text/plane; charset=utf-8', 'handler'=> array($this, 'to_text'))
            );
        }

        public static function init($req = null, $autoRun = true) {
            $inst = static::getSingleton();
            $r = $inst->router;
            $namespace = static::$controllerNamespace;
            $r->addFilters(array(
                ':controller' => function($name) use(&$r, &$namespace) {
                
                    if(!isset(static::$pluralResourceToControler)){
                        $class = $namespace . '\\' . ucwords($name) . 'Controller';
                        if(class_exists($class, TRUE)){
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
                '/:controller/:var' => array($inst, 'capture'),
                '/:controller' => array($inst, 'capture'),
                '/' => array($inst, 'capture')
            ]);
            if($autoRun){
                if(! isset($req)) $req = $_SERVER['REQUEST_URI'];
                $r->check($req);
            }
        }
        
        //Retorna (e instancia si ya no lo está) el singleton del ApiController
        public static function getSingleton(){
            if(! isset(static::$singleton)){
                static::$singleton = new static();
            }
            return static::$singleton;
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
            if (method_exists($obj, $method.'_'.$this->router->requestMethod)) {
                $output = call_user_func(array($obj, $method.'_'.$this->router->requestMethod));
            } 
            //Se el requestMethod solicitado se corresponde con el method a asumir en los metodos del objeto, 
            //se verifica si el metodo existe si en requestMethod como sufijo.
            else if ($this->router->requestMethod === static::$assumeRequestMethod && method_exists($obj, $method)) {
                $output = call_user_func(array($obj, $method));
            } 
            //El metodo de captura ante errores existe en el objeto?
            else if (method_exists($obj, static::$errorHandler)) {
                $output = call_user_func(array($obj, static::$errorHandler));
            } 
            
            else {
                $output = call_user_func(array($this, static::$errorHandler));
            }
            $this->to_format($output);
        }
        
        public function errorHandler($req = '/') {
            echo("El recurso <<".static::$singleton->router->request.">> no se pudo encontrar en este aplicación.");
        }
        
        //Se puede sobrescribir si se quiere decidir como formatear la información.
        protected function to_format($data){
            $oh = $this->outputHandlers[$this->outputFormat];
            if(isset($oh) && is_callable ($oh['handler'])){                
                echo call_user_func_array($oh['handler'], array($data));  
            }
            else {
                echo 'Formato no valido o no expecificado.';
            }
            exit();
        }
        
        //Indicate de data format
		public function addOutputHandlers($handlers){
            array_push($this->outputHandlers, $handlers);
        }
        
        public function setOutputFormat($format){
            if(in_array($format, $this->outputHandlers)){
                $this->outputFormat = $format;
            }
            else {
                $this->outputFormat = static::$defaultOutputFormat;
            }
        }
        
        //Se presentan los datos en formato json
        public function to_json($data){
            header('Content-type: '.$this->outputHandlers['json']['Content-type']);
            echo json_encode($data);
        }
        //Se presentan los datos en formato json
        public function to_array($data){
            return $data;
        }
			 
        //Se presentan los datos en formato jsonp
        public function to_jsonp($data, $params){
            header('Content-type: '.$this->outputHandlers['jsonp']['Content-type']);
            echo $params['callback'].'('.json_encode($data).');';
        }

        //Se presentan los datos en formato text plane
        public function to_text($data, $params){
            header('Content-type: '.$params['Content-type']);
            echo str_parse($data);
        }
        
        //
        public function show($data, $ext='default'){
            $this->to_format($data, $ext);
        }
    }

}

?>
