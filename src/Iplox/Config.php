<?php

    namespace Iplox;
    
    class Config {
        protected static $singleton;
        protected $appDir;
        protected $appNamespace;
        protected $env;
        
        //Crea y/o retorna el singleton.
        public static function getSingleton()
        {
            if(! isset(static::$singleton)){
                static::$singleton = new static();
            }
            return static::$singleton;
        }
        
        //Set the directory of the application
        public static function setAppDir($dir)
        {
            $singleton = static::getSingleton();
            $singleton->appDir = $dir;
        }
        
        //Return the application namespace
        public static function getAppDir(){
            $singleton = static::getSingleton();
            return $singleton->appDir;
        }
        
        //Set the namespace of the application
        public static function setAppNamespace($ns)
        {
            $singleton = static::getSingleton();
            $singleton->appNamespace = $ns;
        }
               
        public static function getAppNamespace(){
            $singleton = static::getSingleton();
            return $singleton->appNamespace;
        }
        
        //Set the enviroment 
        public static function setEnv($env)
        {
            $singleton = static::getSingleton();
            $singleton->env = $env;
        }
        //Return the enviroment value.
        public static function getEnv(){
            $singleton = static::getSingleton();
            return $singleton->env;
        }
        
        //Return a configuration class, if exists under the conventional configuration directories
        public static function get($cfg)
        {
            $inst = static::getSingleton();
            if(! isset($cfg))
            {
                return null;
            }
            else if(class_exists($class = $inst->appNamespace."\\Config\\".$cfg."Config"))
            {
                return $class;                
            }
            else if(class_exists($class = $inst->appNamespace."\\Config\\".$inst->env."\\$cfg"."Config"))
            {
                return $class;                
            }
            else
            {
                return "Iplox\\Config\\".$cfg."Config"; 
            }
        }
        
    }