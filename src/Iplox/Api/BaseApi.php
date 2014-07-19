<?php

namespace Iplox\Api {
    use Iplox\Util;
    use Iplox\Api\BaseTarget;
    
    class BaseApi {
        protected $fields;
        protected $params;
        
        protected $formats;
        public $request;
        public $verb;
        public $selectedRoute;
        private $ext;
                  
        public function __construct($fields, $params, $follow=true, $req=null, $verb=null, $output_formats=null){				
            //
            $this->fields = $fields;
            $this->params = $params;
            
            //Si follow = true, la funcion check retornará un areglo con los datos.
            //De modo que si se quiere deveolver al cliente, habrá que llamar la funcion $this->show().
            $this->follow = isset($follow) ? $follow : false;
            
            //Formatos de salida válidos
            $this->formats = isset($output_formats) ? $output_formats : 
               array(
                  'json'=>    array('Content-type'=>'application/json', 'default'=> true),
                  'jsonp'=>   array('Content-type'=>'application/javascript', 'callback'=>'callback'),
                  'array'=>   null,
                  'text'=>array('Content-type'=>'text/plane; charset=utf-8')
               );
            
            //Si no se indica el path o request. Se extrae la solicitud de la uri.
            if(!isset($req)){
               $this->request = Util::getApiRequestedPath();
            }
            else $this->request = $req;
            
            //Si no se espesifica un método en el parámetro $verb, se extrae de la variable $_SERVER;
            $this->verb = (!isset($verb)) ? $_SERVER['REQUEST_METHOD'] : $verb;
        }
        
        public function check($routes, $target=null, $isLocal=false, $req=null, $verb=null){
            if(!isset($req)){
                if(isset($this->follow) && isset($this->selectedRoute)){
                    $adapRegex = "/^".preg_replace('/\//', '\\/', $this->selectedRoute)."/";
                    $req = preg_replace($adapRegex, '', $this->request);
                    if($req == '.json') $req = '';
                }
                else {
                   $req = $this->request;
                   $extArr = array();
                   preg_match('/\.([a-zA-Z0-9])+/', $req, $extArr); 
                   //La primera coincidencia es la extensión.
                   $this->ext = (count($extArr)>0 ? str_replace('.', '', $extArr[0]) : 'default');
                   $req = preg_replace('/\.'.$this->ext.'$/', '', $req);
                }
            }
            //Si el request es '' o igual a la extensión se pone por defecto '/'
            if($req==='' || $req === ".".$this->ext) $req = '/';
            
            //$verb es el método HTTP requerido
            $verb = (isset($verb)) ? $verb : $this->verb;
            
            $matches = array();  
            
            foreach($routes as $endpoint => $callbacks){
                $pattern = preg_replace('/\\\:[a-zA-Z0-9\_\-]+/', '([a-zA-Z0-9\-\_]+)', preg_quote($endpoint));		
                $pattern = ($pattern === '') ? '/^//' : "/^".preg_replace('/\//', '\\/', $pattern)."/";   
                if(preg_match($pattern, $req, $matches)){            
                    //La primera coincidencia no cuenta. Posee el path completo.
                    array_shift($matches);

                    if(isset($this->selectedRoute)) { 
                        $this->selectedRoute .= $endpoint;
                    }
                    else $this->selectedRoute  = $endpoint;
                    
                    if(is_callable($callbacks)){
                       $data = call_user_func_array($callbacks, $matches);
                    }	
                    else if(is_array($callbacks)){
                        if(isset($target) && is_callable(array($target, $callbacks[strtolower($verb)]))){
                            if($target instanceof BaseTarget){
                                if(isset($this->fields)) $target->setFields($this->fields);
                                if(isset($this->params)) $target->setParams($this->params); 
                            }
                            $data = call_user_func_array(array($target, $callbacks[strtolower($verb)]), $matches);
                        }
                        else if(function_exists($callbacks[strtolower($verb)]))
                            $data = call_user_func_array($callbacks[strtolower($verb)], $matches);
                    }
                    else {
                       $data = false;
                    }
                    break;
                }
            }
            
            $data = isset($data) ? $data : array();
            
            //Si se cumple, se devuelven los datos para un procesamiento posterior-local
            if(isset($isLocal) || isset($this->follow))
               return $data;
            //De otro modo se devuelve al cliente los datos solicitados.
            else 
               $this->to_format($data, $this->ext);
         }
         
        //Se puede sobrescribir si se quiere decidir como formatear la información.
        protected function to_format($data, $ext){
            if(isset($ext) && isset($data) && array_key_exists($ext, $this->formats)){
                call_user_func_array(array($this, "to_".$ext), array($data, $this->formats[$ext]));
            }	
            else if($ext == 'default'){
                foreach($this->formats as $k => $v){
                    if(isset($v['default'])) {
                        call_user_func_array(array($this, "to_".$k), array($data, $this->formats[$k]));
                        break;
                    }
                }
            }
            else {
                return 'Formato no válido o no expesificado.';
            }
        } 
			
        //Se presentan los datos en formato json
        public function to_json($data, $params){
            header('Content-type: '.$params['Content-type']);
            echo json_encode($data);
        }
			 
        //Se presentan los datos en formato jsonp
        public function to_jsonp($data, $params){
            header('Content-type: '.$params['Content-type']);
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
