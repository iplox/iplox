<?php

    namespace Iplox;

    class Config {

        // Array of key => values of all configurations store in memory.
        protected $keyVals;
        // Used when called the method getSet for caching configs arrays.
        protected $setConfig;
        // List of known properties. Useful for speeding up some dynamic properties retreaving.
        protected $knownConfigs;

        // Constructor method.
        public function __construct(Array $keyVals = []){
            $this->keyVals['General'] = $keyVals;
            $this->setConfig = [];
            $this->knownConfigs = [
                'General' => [
                    'appDir',
                    'moduleDir',
                    'modelsDir',
                    'controllersDir',
                    'viewsDir',
                    'bundlesDir',
                    'namespace',
                    'env'
                ],
                'Db' => [
                    'provider',
                    'username',
                    'hostname',
                    'dbname',
                    'password'
                ]
            ];
        }

        /*
         *  Method for capturing dynamic values.
         */
        public function __get($name){
            if(array_key_exists($name, $this->knownConfigs['General'])){
                return $this->get($name);
            }
        }


        /*
         *  Method for capturing dynamic values.
         */
        public function __set($name, $value){
            if(in_array($name, $this->knownConfigs['General'])){
                $this->keyVals['General'] = $value;
            }
        }

        /*
         *  Return a set of configurations key/values, if exist under the conventional configuration directories
         */
        public function getSet($setName) 
        {
            if (!isset($setName)) {
              return null;
            }

            // If the setName config are already cached there is no need to load from filesystem again.
            if(array_key_exists($setName, $this->setConfig)){
                return $this->setConfig[$setName];
            }

            $cfgSpecific = [];
            $cfgBase = [];
            $cfgDefault = [];

            if (is_readable($fName = $this->appDir . DIRECTORY_SEPARATOR . "Config" . DIRECTORY_SEPARATOR . $this->env . DIRECTORY_SEPARATOR . $setName . "Config.php")) {
                $cfgSpecific = @include $fName;
            }

            if (is_readable($fName = $this->appDir . DIRECTORY_SEPARATOR . "Config" . DIRECTORY_SEPARATOR . $setName . "Config.php")) {
              $cfgBase = @include $fName;
            }

            if( is_readable($fName =__DIR__.DIRECTORY_SEPARATOR."Config" . DIRECTORY_SEPARATOR . $setName . "Config.php")){
                $cfgDefault = @include $fName;
            }

            // Merge all sources.   
            $cfg = array_merge($cfgDefault, $cfgBase, $cfgSpecific);

            // Cache the $setName
            $this->setConfig[$setName] = $cfg;

            return $cfg;
        }

        /**
        *   Return the value of a config. It accepts the setName as optional param. 
        *   @param string $key Name of the config.
        *   @param string $setName optional Name of the setName.
        *   @return $mixed Whatever the value is.
        */
        public function get($key, $setName = 'General')
        {            
            if(! is_string($key) && !is_array($key)){
                throw new \Exception("Expected an string or array as first argument.");
            } else if(is_string($key)){
                if(array_key_exists($key, $this->keyVals[$setName])){
                    return $this->keyVals[$setName][$key];
                } else {
                    return $this->getSet($setName)[$key];
                }
            } else if(is_array($key)){
                $matched = [];
                foreach ($key as $k) {
                    echo gettype($this->keyVals[$setName]);
                    if(array_key_exists($k, $this->keyVals[$setName])){
                        $matched[$k] = $this->keyVals[$setName][$k];
                    } else {
                        $matched[$k] = $this->getSet($setName)[$k];
                    } 
                }
                return $matched;
            }
        }

        /**
        *   Set the value of a config. It accepts a setName, by default is 'General'.  
        *   @param string $key Name of the config.
        *   @param string $val optional null The value of the config.
        *   @param string $setName optional Name of the setName.
        */
        public function set($key, $val = null, $setName = 'General')
        {
            if(! is_string($key) && !is_array($key)){
                throw new \Exception("Expected an string or array as first argument.");
            } else if(is_string($key)){
                $this->keyVals[$setName][$key] = $val;        
            } else if(is_array($key)){   
                $this->keyVals[$setName] = \array_merge($this->keyVals[$setName], $key);
            }
        }
    }
