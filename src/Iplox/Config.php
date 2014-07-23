<?php

    namespace Iplox;
    
    class Config {
        protected static $singleton;
        protected $appDir;
        protected $appNamespace;
        protected $env;
        
        //Retorna (e instancia si ya no lo está) el singleton del ApiController
        public static function getSingleton()
        {
            if(! isset(static::$singleton)){
                static::$singleton = new static();
            }
            return static::$singleton;
        }
        
        public function setAppDir($dir)
        {
            $singleton = static::getSingleton();
            $singleton->appDir = $dir;
        }
        
        public function setAppNamespace($ns)
        {
            $singleton = static::getSingleton();
            $singleton->appNamespace = $ns;
        }
                
        public function setEnv($env)
        {
            $singleton = static::getSingleton();
            $singleton->env = $env;
        }
        
        public function get($cfg)
        {
            $inst = static::getSingleton();
            if(class_exists($class = $inst->appNamespace."\\Config\\".$cfg."Config"))
            {
                return $class;                
            }
            else if(class_exists($class = $inst->appNamespace."\\Config\\".$inst->env."\\$cfg"."Config"))
            {
                return $class;                
            }
            else
            {
                return "Iplox\\Config\\".$inst->env."\\$cfg"."Config"; 
            }
        }
    }