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

    // Array of directories where config files might exist.
    protected $files = [];

    // Constructor method.
    public function __construct(Array $options = [])
    {
        // Default configurations
        $this->options["default"] = $options;
        // * means dynamic files that apply to any $optionSet.
        $this->files['*'] = [];
        $this->addKnownOptions("default", []);
    }

    /*
     *  Method for retrieving options magically.
     */
    public function __get($name){
        if($this->isKnownOption($name)){
            return $this->get($name);
        }
        return null;
    }


    /*
     *  Method for setting options magically.
     */
    public function __set($name, $value){
        $this->options["default"][$name] = $value;
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

        if(!array_key_exists($setName, $this->options) || !is_array($this->options[$setName])){
            $this->options[$setName] = [];
        }

        // Merge all sources.
        $cfg = array_merge(
            $this->knownOptions[$setName],
            $this->getOptionsFromFiles($setName),
            $this->options[$setName]);

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
    public function get($key, $setName = "default")
    {
        if(! (is_string($key) || is_array($key))){
            throw new \Exception("Expected an string or array as first argument.");
        } else if(is_string($key)){
            if(array_key_exists($setName, $this->options) && array_key_exists($key, $this->options[$setName])){
                $v = $this->options[$setName][$key];
                if($v instanceof \Closure){
                    return call_user_func([$this, $v]);
                }
                return $v;
            } else {
                $opts = $this->getSet($setName);
                if(array_key_exists($key, $opts)){
                    return $opts[$key];
                }
                return null;
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
                    $opts = $this->getSet($setName);
                    if(array_key_exists($k, $opts)){
                        $matched[$k] = $opts[$k];
                    }
                }
            }
            return $matched;
        }
    }

    /**
    *   Set the value of a config. It accepts a setName, by default is "default".
    *   @param string $key Name of the config.
    *   @param string $val optional null The value of the config.
    *   @param string $setName optional Name of the setName.
    */
    public function set($key, $val = null, $setName = "default")
    {
        if(! is_string($key) && !is_array($key)){
            throw new \Exception("Expected a string or an array as first argument.");
        } else if(is_string($key)){
            $this->options[$setName][$key] = $val;
        } else if(is_array($key)){
            $this->options[$setName] = \array_merge(
                empty($this->options[$setName]) ? [] : $this->options[$setName],
                $key
            );
        }
    }


    // This function add known options to use as configuration options.
    public function addKnownOptions($setName, Array $options = null){
        if(is_array($setName)){
            $options = $setName;
            $setName = "default";
        }
        else if(! array_key_exists($setName, $this->knownOptions)){
            $this->knownOptions[$setName] = [];
        }
        $this->knownOptions[$setName] = array_merge($this->knownOptions[$setName], $options);
	}

    // Check if an config option is valid for an specific config set.
    public function isKnownOption($option, $setName = "default")
    {
        return array_key_exists($option, $this->knownOptions[$setName]);
    }

    // Add configuration directories
    public function addFile($file, $setName = "default")
    {
        if(! array_key_exists($setName, $this->files)){
            $this->files[$setName] = [];
        }
        array_push($this->files[$setName], $file);
    }

    // Extract options from all configuration files
    public function getOptionsFromFiles($setName)
    {
        $allOpts = [];
        $files = $this->files['*'];
        if(array_key_exists($setName, $this->files)) {
            foreach($this->files[$setName] as $f){
                array_push($files, $f);
            }
        }

        foreach($files as $f) {
            if(is_callable($f)) {
                $f = call_user_func($f, $setName);
            }
            if (is_readable($f)) {
                $opts = @include $f;
                $allOpts = array_merge($allOpts, $opts);
            }
        }
        return $allOpts;
    }

    public function refreshCache($setName = "default")
    {
        $optsFromFiles = $this->getOptionsFromFiles($setName);
        $this->cacheSets[$setName] = array_merge(
            $this->knownOptions[$setName],
            $optsFromFiles
        );
    }
}
