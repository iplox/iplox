<?php
	
namespace Iplox {
   use \PDO;
   use Iplox\Enum\DbEnum as Db;
    class Util {

        static public function cast($obj, $className){
            //Si $obj es un array se convierte en un objeto.
            if(is_array($obj)) $obj = (object) $obj;
            $res = "O:".strlen($className).':"'.$className.'"'.preg_replace(
                    "/^O:[0-9]*:\"[a-zA-Z0-9]*\"/",
                    '',
                    serialize($obj));
            return unserialize($res);
        }
        
        //Devuelve el path solicitado
        static public function getApiRequestedPath($q=''){
            if($q==='' || !isset($q)) 
                $reg = '/^'.dirname($_SERVER['SCRIPT_NAME']).'/';
            else 
                $reg = $q;
            
            return preg_replace('/^\/?|\/?$/', '', preg_replace('/\?.*$/', '', str_replace($reg, '', $_SERVER['REQUEST_URI'])));	
        }
        
        //
        static public function getApiRequestedParams(){
            return $_REQUEST;
        }
        
        
         static function getPdo($dbData) {
            //Conectando con la base de datos
            if (array_key_exists(Db::PROVIDER, $dbData) &&
               array_key_exists(Db::HOST, $dbData) &&
               array_key_exists(Db::NAME, $dbData) &&
               array_key_exists(Db::USER, $dbData) &&
               array_key_exists(Db::PASS, $dbData)) {
               try {
                  $link = new PDO($dbData[Db::PROVIDER] . ":dbname=" . $dbData[Db::NAME] . ";host=" . $dbData[Db::HOST] . ";charset=utf8", $dbData[Db::USER], $dbData[Db::PASS]);
                  $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                  $link->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                  return $link;
                  //echo "PDO connection object created";
               } catch (PDOException $e) {
                  throw new Exeption($e->getMessage());
               }
            }
            return false;
         }
        
    }
}

?>
