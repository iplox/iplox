<?php

namespace Iplox\Mvc\View {
   use \Smarty;
   
   class SmartyView {
        public $engine;
        protected static $singleton;
            
        public function __construct(){}
        public function __clone(){}
        
        public static function setup($cfg=array()){
            $singleton = static::getSingleton();
            static::$singleton->engine = new Smarty();
            
            if(array_key_exists('templateDir', $cfg)){
                static::$singleton->setTemplateDir($cfg['templateDir']);
            }
            
            if(array_key_exists('cacheDir', $cfg)){
                static::$singleton->setCacheDir($cfg['cacheDir']);
            }
            
            if(array_key_exists('compileDir', $cfg)){
                static::$singleton->setCompileDir($cfg['compileDir']);
            }
            
            if(array_key_exists('pluginsDir', $cfg)){
                static::$singleton->addPluginsDir($cfg['pluginsDir']);
            }
            
            if(array_key_exists('vars', $cfg)){
                static::$singleton->assign($cfg['vars']);
            }
            
        }
        
        public static function getSingleton(){
            //Si no existe el singleton se crea y se devuelve.
            if(!isset(static::$singleton)){
                static::$singleton = new static();
                return static::$singleton;
            }
            else { 
                return static::$singleton;
            }
        }
        
        public function display($templateFile){
            $this->engine->display($templateFile);
        }
        
        public function assign($arrOrVarName, $arr=null){
            $this->engine->assign($arrOrVarName, $arr);
        }
        
        public function setTemplateDir($templateDir){
            $this->engine->setTemplateDir($templateDir);
            return $this;
        }
        
        public function setCacheDir($cacheDir){
            $this->engine->setCacheDir($cacheDir);
            return $this;
        }
        
        public function setCompileDir($compileDir){
            $this->engine->setCompileDir($compileDir);
            return $this;
        }
        
        public function addPluginsDir($pluginsDir){
            $this->engine->addPluginsDir($pluginsDir);
            return $this;
        }
        public function setPluginsDir($pluginsDirs){
            $this->engine->setPluginsDir($pluginsDirs);
            return $this;
        }
   }
}

?>
