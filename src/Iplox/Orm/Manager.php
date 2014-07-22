<?php
namespace Iplox\Orm {
    use \PDO;
    use \Exception;
    
    class Manager {
        public $q;
        protected $params;
        protected $inner;
        public $link;
        public $dftDbName;
        protected static $singleton;
        
        protected $transaction = '';
        
        private function __clone() {}
        private function __construct() {}
        
        public static function setup($host, $login, $password, $dbname=null, $dbprovider="mysql"){
            try {
                $connStr = $dbprovider.":".
                    "host=".$host. 
                    ";charset=utf8"; 
                $link = new PDO($connStr, $login, $password);
            }
            catch(Exception $e){
                echo $e->getMessage();
                exit();
            }
            
            $singleton = static::getSingleton();
            static::$singleton->link = $link;
            
            //Selecciona la base de datos. Si no existe, creará una con el nombre indicado.
            $singleton->useDb($dbname);
                        
            return $singleton;
        }
        
        public static function useDb($dbname, $forceCreation=false){
            $link = Manager::getSingleton()->link;
            
            if($forceCreation) {
                $q = $link->prepare("CREATE DATABASE IF NOT EXISTS $dbname");
                $q->execute();
            }
            
            $q = $link->prepare("USE ".$dbname);
            $affected = $q->execute();
            
            if($affected != 1){
                throw new Exception("La Base de Datos \"$dbname\" no existe.");
            }
            return true;
        }
        
        public static function getSingleton(){
            //Se retorna el singleton
            if(!isset(static::$singleton)){
                static::$singleton = new self();
                return static::$singleton;
            }
            else { 
                return static::$singleton;
            }
        }
        
        //Database Low Level Operations
        public function select($colums="*"){
           $this->q = $this->q." SELECT $colums ";
           return $this;
        }
        
        public function from($table=null){
           if(isset($table))
              $this->q = $this->q. " FROM ".$table;
           else
              throw new Exception ("Nombre de tabla no válido.");
           return $this;
        }

        public function inner($innerTable){
            $this->inner = " INNER JOIN ".$innerTable;
            return $this;
        }

        public function on($cond){
            if(isset($this->inner)){
                $this->q = $this->q.$this->inner." ON ".$cond;
            }
            $this->inner = null;
            return $this;
        }
        public function delete($tableName){
            $this->q = " DELETE ".$tableName;
        }
        
        public function where($where=null){
            if(empty($where) AND !isset($where)){
                return $this;
            }
            $this->q = $this->q." WHERE ".$where;
            return $this;
        }

        public function order($by, $dir){
           $this->q = $this->q . " ORDER BY $by $dir";
           return $this;
        }
        public function limit($limit){
           $this->q = $this->q . " LIMIT $limit";
           return $this;
        }

        public function offset($offset){
            $this->q = $this->q . " OFFSET $offset";
            return $this;
        }

        public function x($params = null){
            $p = Manager::getSingleton()->link->prepare($this->q);
            if(isset($params) && is_array($params)){
                $r = $p->execute($params);
            }
            else {
                $r = $p->execute();
            }
            $this->clear();
            
            if($this->transaction === 'UPDATE'){
                
                return $r;
            }
            return $p->fetchAll(PDO::FETCH_ASSOC);
        }
        
        public function insert($tblName, $fieldsName=null){
            $f = ''; $separator = '';
            $pdoP = '';
            if(isset($fieldsName)){
                foreach($fieldsName as $v){
                    $f .= $separator. '`'.$v.'`';
                    $pdoP .= $separator.'?';
                    $separator = ',';
                }
                $f = '('.$f. ')';
            }
            $this->q = "INSERT INTO $tblName $f";
            
            return $this;
        }
        
        public function values($valsNames){
            $values = ''; $separator = '';
            if(isset($valsNames) && is_array($valsNames)){
                foreach($valsNames as $v){
                    $values .= $separator. '\''.$v.'\'';
                    $separator = ',';
                }
            }
            else if(is_string($valsNames)){
                $values = $valsNames;
            }
            
            $this->q .= " VALUES (".$values.")";
            
            return $this;
        }
        
        public function update($tblName, $fieldsNames=null){
            $this->transaction = 'UPDATE';
            $f = ''; $separator = '';
            if(isset($fieldsNames)){
                foreach($fieldsNames as $v){
                    $f = $f.$separator. '`'.$v.'` = :'.$v;
                    $separator = ',';
                }
            }
            $this->q = "UPDATE $tblName SET $f";
            return $this;
        }
        
        //Reestablece el objeto orm para otro request.
        public function clear(){
            $this->transaction = "";
            $this->q='';
        }
        
        //En un arreglo asociativo agrega ':' a cada key.
        public function toPdoParams($data){
            $d = [];
            foreach($data as $k=> $v){
                $d[':'.$k] = $v;
            }
            return $d;
        }
    }
}
