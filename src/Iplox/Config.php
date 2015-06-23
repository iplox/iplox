<?php

namespace Iplox;

class Config
{
    // Array of key => values of all configurations store in memory.
    protected $options = [];
    // Used when the method getSet is called for caching configs arrays.
    protected $cacheSets = [];
    // List of known properties. Useful for speeding up some dynamic properties retreaving.
    protected $knownOptions = [];

    protected $directory;
    protected $namespace;
    protected $env;
    protected $configDir;

    // Constructor method.
    public function __construct($directory, $namespace = '', Array $options = [], $env = 'Development'){
        $this->directory = $directory;
        $this->namespace = $namespace;
        $this->env = $env;

        $this->configDir = $directory . DIRECTORY_SEPARATOR .
            (empty($namespace) ? '' : (str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR)) .
            "Config" . DIRECTORY_SEPARATOR;

        // Default configurations
        $this->options["Default"] = $options;
        $this->addKnownOptions("Default", []);
    }

    /*
     *  Method for retrieving options magically.
     */
    public function __get($name){
        if($this->isKnownOption($name)){
            return $this->get($name);
        } else if($name === 'namespace'){
            return $this->namespace;
        } else if($name === 'directory'){
            return $this->directory;
        } else if($name === 'env'){
            return $this->env;
        }
        return null;
    }


    /*
     *  Method for setting options magically.
     */
    public function __set($name, $value){
//        if($this->isKnownOption($name)){
            $this->options["Default"][$name] = $value;
//        }
    }

    /*
     *  Return a set of options (associative array), if exists under the configuration directories
     */
    public function getSet($setName)
    {
        if (!isset($setName)) {
          return null;
        }

        // If the optionSet is already cached there is no need to load from filesystem again.
        if(array_key_exists($setName, $this->cacheSets)){
            return $this->cacheSets[$setName];
        }

        if($setName === "Default"){
            $prefix = '';
        } else {
            $prefix = $setName;
        }

        $cfgSpecific = [];
        $cfgBase = [];

        if (is_readable($fName = $this->configDir  . $this->env . DIRECTORY_SEPARATOR . $prefix . "Config.php")) {
            $cfgSpecific = @include $fName;
        }

        if (is_readable($fName = $this->configDir . $prefix . "Config.php")) {
            $cfgBase = @include $fName;
        }

        // Merge all sources.
        $cfg = array_merge($this->knownOptions[$setName], $cfgBase, $cfgSpecific);

        // Cache the set of options
        $this->cacheSets[$setName] = $cfg;

        return $cfg;
    }

    /**
    *   Return the value of a config. It accepts the setName as optional param.
    *   @param string $key Name of the config.
    *   @param string $setName optional Name of the setName.
    *   @return $mixed Whatever the value is.
    */
    public function get($key, $setName = "Default")
    {
        if(! (is_string($key) || is_array($key))){
            throw new \Exception("Expected an string or array as first argument.");
        } else if(is_string($key)){
            if(array_key_exists($key, $this->options[$setName])){
                $v = $this->options[$setName][$key];
                if($v instanceof \Closure){
                    return call_user_func([$this, $v]);
                }
                return $v;
            } else {
                return $this->getSet($setName)[$key];
            }
        } else if(is_array($key)){
            $matched = [];
            foreach ($key as $k) {
                if(array_key_exists($k, $this->options[$setName])){
                    $v = $this->options[$setName][$k];
                    if($v instanceof \Closure){
                        $matched[$k] = call_user_func([$this, $v]);
                    } else {
                        $matched[$k] = $v;
                    }
                } else {
                    $matched[$k] = $this->getSet($setName)[$k];
                }
            }
            return $matched;
        }
    }

    /**
    *   Set the value of a config. It accepts a setName, by default is "Default".
    *   @param string $key Name of the config.
    *   @param string $val optional null The value of the config.
    *   @param string $setName optional Name of the setName.
    */
    public function set($key, $val = null, $setName = "Default")
    {
        if(! is_string($key) && !is_array($key)){
            throw new \Exception("Expected an string or array as first argument.");
        } else if(is_string($key)){
            $this->options[$setName][$key] = $val;
        } else if(is_array($key)){
            $this->options[$setName] = \array_merge($this->options[$setName], $key);
        }
    }


    // This function add known options to use as configuration options.
    public function addKnownOptions($setName, Array $options = null){
        if(is_array($setName)){
            $options = $setName;
            $setName = "Default";
        }
        else if(! array_key_exists($setName, $this->knownOptions)){
            $this->knownOptions[$setName] = [];
        }
        $this->knownOptions[$setName] = array_merge($this->knownOptions[$setName], $options);
	}

    // Check if an config option is valid for an specific config set.
    public function isKnownOption($option, $setName = "Default")
    {
        return array_key_exists($option, $this->knownOptions[$setName]);
    }
}
