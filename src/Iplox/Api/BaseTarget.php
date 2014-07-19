<?php

namespace Iplox\Api {
    use Iplox\Rbac\Exception;
    use Iplox\Rbac\ExceptionCode;
    use \PDO;
	
    class BaseTarget {
        protected $callbacks;
        public $params;
        public $fields;
        
        public function __construct($fields = array(), $params = array()){
            $this->setFields($fields);
            $this->setParams($params);
        }
		
        //Listados de parámetros reconocibles por la API
        public function setParams($params){
            if(is_array($params)) $this->params = $params;
            else throw new Exception("La variable no es un arreglo.");
        }
        
        //Listados de campos reconocibles por la API
        public function setFields($fields){
            if(is_array($fields)) $this->fields = $fields;
            else throw new Exception("La variable no es un arreglo.");
        }
        
        //Devuelve los parámetros de la solicitud combinados con sus valores por defecto.
        //$dftParams indica cuales parámetros se incluirán como defaults.
        protected function getParams($context){
            if(!array_key_exists($context, $this->params)) return false;
            $params = $this->params[$context];
            foreach($params as $k => $v)
                $params[$k] = (isset($_REQUEST[$k])) ? $_REQUEST[$k] : $v;
            return $params;
        }
    }
}
?>
