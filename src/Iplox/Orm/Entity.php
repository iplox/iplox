<?php

namespace Iplox\Orm {
   use \Exception;
   use \PDO;
   use Iplox\Orm\Manager;
   
   class Entity {
        public static $TableName = 'DefaultTable';
        public static $PrimaryKey = 'Id';
        public static $Fields= [];
        
        public $ToSave = [];
        
        //Acceso a las propiedades de la entidad.
        public function __set($fldName, $value){
            if(array_key_exists($fldName, static::$Fields)){
                if(method_exists($this, 'before_set')){
                    call_user_func(array($this, 'before_set'));
                }
                $this->ToSave[$fldName] = $value;
                
                if(method_exists($this, 'after_set')){
                    call_user_func(array($this, 'before_set'));
                }
            }
            else {
                throw new Exception("No existe la propiedad solicitada.");
            }
        }
        
        public function __get($fldName){
            if(array_key_exists($fldName, static::$Fields)){
                return $this->_Value($fldName);
            }
        }
                
        //Devuelve o modifica un campo privado a partir del $fldName.
        protected function _Value($fldName, $value=null){
            $realName = static::$Fields[$fldName]['name'];
            $vars = get_object_vars($this);
            if(!is_null($value) ){
                $vars[$realName] = $value;
                return $value;
            }
            else {
                return $vars[$realName];
            }
        }
        
        public function delete(){
            $orm = Manager::getSingleton();
            $pk = $this->_Value(static::$PrimaryKey);
            $del = "DELETE FROM ".static::getTable()." WHERE ".static::$PrimaryKey." = ?";
            $p = $orm->link->prepare($del);
            if($p->execute([$pk]) > 0){
                return true;
            }
            else {
                return false;
            }
        }
        
        //Inserta o actualiza los datos en la tabla.
        public function save(){
            $orm = Manager::getSingleton();
            $pk = $this->_Value(static::$PrimaryKey);
            
            if(isset($pk) && $pk > 0){
                //Update                
                $sep = "";
                $updates = "";
                $toSaveValues=[":".static::$PrimaryKey=>$pk];
                foreach($this->ToSave as $k => $v){
                    if(strlen($updates)>0) { $sep=", "; }
                    $updates = $updates.$sep." `$k`=:$k";
                    $toSaveValues[":$k"] = $v;
                }
                $upd = "UPDATE ".static::getTable()." SET ".$updates." WHERE ". static::$PrimaryKey."=:".static::$PrimaryKey;
                
                $p = $orm->link->prepare($upd);
                $affected = $p->execute($toSaveValues);
                if($affected <= 0){
                    return false;
                }
            }
            else {
                //Insert            
                $sep = "";
                $fields = "";
                $values = "";
                $toSaveValues = [];
                foreach($this->ToSave as $k => $v){
                    if(strlen($fields)>0){ 
                        $sep=", ";
                    }
                    $fields = $fields.$sep." $k";
                    $values = $values.$sep.":$k";
                    $toSaveValues[":$k"] = $v;
                }
                $in = "INSERT INTO ".static::$TableName."(".$fields.") VALUES(".$values.")";
                $p = $orm->link->prepare($in);
                
                if($p->execute($toSaveValues) == 1){
                    //Se actualiza las propiedades del objeto.
                    $this->_Value(static::$PrimaryKey, $orm->link->lastInsertId());
                }
                else {
                    return false;
                }
            }
           
            foreach($this->ToSave as $k=>$v){
                $this->_Value($k, $v);
            }
            $this->ToSave = [];
        }
        
        public function discard(){
            
        }
        
        //Static Member
        public static function getTable(){
            if(isset(self::$DbName)){
                return self::$DbName.".".static::$TableName;
            }
            else if(isset(Manager::getSingleton()->DbName)) {
                return Manager::getSingleton()->DbName.".".static::$TableName;
            }
            else {
                return static::$TableName;
            }  
        }
        
        public static function getRecords($limit=10, $offset=0){
            $orm = Manager::getSingleton();
            $records = $orm->select("*")->from(static::getTable())->limit($limit)->offset($offset)->x(array());
            return $records;
        }
        
        public static function getRecordById($id){
            //
            $orm = Manager::getSingleton();
            $records = $orm->select("*")->from(static::getTable())->where(static::$PrimaryKey." = ?")->x(array($id));
            return $records[0];
        }
        
        public static function getRecordDefaults(){
            foreach(static::$Fields as $k => $f){
                $r[$k] = (isset($f['default']) ? $f['default'] : null);
            }
            return $r;
            
        }
        
        public static function find($limit=10, $offset=0){
            //
            $records = static::getRecords($limit=10, $offset=0);
            $list = [];
            foreach($records as $r){
                array_push($list, new static($r));
            }
            return $list;
        }
        
        public static function findById($id){
            //
            $record = static::getRecordById($id);
            if(isset($record) > 0){
                return new static($record);
            }
            else {
                return null;
            }
        }
        
        public static function remove($conditions){
            
        }
        
        public static function add(){
            
        }
        
        
   }
   
}

?>
