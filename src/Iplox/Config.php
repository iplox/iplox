<?php

    namespace Iplox;

    class Config {
        protected static $singleton;
        protected $appDir;
        protected $appNamespace;
        protected $env = 'Development';

        // The constructor must be hidden in orther to implement the Factory pattern accurately.
        protected function __construct(){}

        //Crea y/o retorna el singleton.
        protected static function getSingleton() {
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

        //Return the directory of the modules
        public static function getModulesDir(){
            $cfg = static::get('General');
            return $cfg['modules_dir'];
        }

        //Set the namespace of the application
        public static function setAppNamespace($ns) {
            $singleton = static::getSingleton();
            $singleton->appNamespace = $ns;
        }

        public static function getAppNamespace() {
            $singleton = static::getSingleton();
            return $singleton->appNamespace;
        }

        //Set the enviroment
        public static function setEnv($env) {
            $singleton = static::getSingleton();
            $singleton->env = $env;
        }

        //Return the enviroment value.
        public static function getEnv() {
            $singleton = static::getSingleton();
            return $singleton->env;
        }

        //Return a configuration array, if exists under the conventional configuration directories
        public static function get($cfgFile) {

            $cfgSpecific = [];
            $cfgBase = [];
            $cfgDefault = [];

            $inst = static::getSingleton();

            if (!isset($cfgFile)) {
              return null;
            }

            if (is_readable($fName = $inst->appDir . DIRECTORY_SEPARATOR . "Config" . DIRECTORY_SEPARATOR . $inst->env . DIRECTORY_SEPARATOR . $cfgFile . "Config.php")) {
                $cfgSpecific = @include $fName;
            }

            if (is_readable($fName = $inst->appDir . DIRECTORY_SEPARATOR . "Config" . DIRECTORY_SEPARATOR . $cfgFile . "Config.php")) {
              $cfgBase = @include $fName;
            }

            if( is_readable($fName =__DIR__.DIRECTORY_SEPARATOR."Config" . DIRECTORY_SEPARATOR . $cfgFile . "Config.php")){
                $cfgDefault = @include $fName;
            }
            $cfg = array_merge($cfgDefault, $cfgBase, $cfgSpecific);
            return $cfg;
        }
    }
