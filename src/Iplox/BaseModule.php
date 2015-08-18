<?php

namespace Iplox;
use Iplox\Http\Request;
use Composer\Autoload\ClassLoader;

class BaseModule extends AbstractModule {

    protected $router;
    protected $children;
    protected $parent;
    protected $modulesToLoad;
    protected $injections;
    protected $baseUrl;

    public function __construct(Config $cfg, AbstractModule $parent = null, Array $injections = null)
    {
        //Add options for a General set.
        $cfg->addKnownOptions([
            // General options.
            'defaultContentType' => 'application/json',

            // Submodules options
            'modules' => [],
            'modulesDir' => 'modules',
            'publicDir' => '../public',
            'moduleClassName' => __CLASS__,
            'autoload' => false,
            'return' =>  false
        ]);

        //Add options for a Db (database) set.
        $cfg->addKnownOptions('db', [
            'driver' => 'pdo_mysql',
            'username' => 'root',
            'password' => '',
            'hostname' => 'localhost',
            'port' => '3386',
            'charset' => 'utf8',
            'dbname' => 'IploxApp',
        ]);

        parent::__construct($cfg);

        // The parent module
        $this->parent = $parent;

        // The children modules
        $this->children = [];
        $this->modulesToLoad = [];

        // Pass this objects to the children submodules
        $this->injections = empty($injections) ? [] : $injections;

        // The router of this module
        $this->router = new Router();
    }

    public function __get($name)
    {
        if($name === 'router'){
            return $this->router;
        } elseif ($name === 'baseUrl'){
            return $this->baseUrl;
        }
        return parent::__get($name);

    }

    // If it has submodules, add the routes.
    protected function addModuleRoutes()
    {
        $modules = $this->config->modules;
        if(empty($modules)){
            return 0;
        }
        $modCfg = [];
        $routes = [];
        $id = 0;

        foreach($modules as $m){
            if(!array_key_exists('default', $m) || !is_array($m['default'])){
                $m['default'] = [];
            }
            $m['default']['id'] = $id++;

            // This will ease the posterior modules autoloading process.
            if(array_key_exists('autoload', $m['default']) && $m['default']['autoload'] === true) {
                $this->modulesToLoad[$m['default']['id']] = $m;
            }

            if(!array_key_exists('route', $m['default']) && ! empty($m['default']['route'])) {
                continue;
            }

            $modCfg[$m['default']['route']] = $m;
            $routes[$m['default']['route']] = function () use (&$modCfg) {
                call_user_func([$this, 'callModule'], $modCfg[$this->router->route]);
            };
        }
        $this->router->prependRoutes($routes);
    }

    protected function loadModule($modCfgArray, $modId)
    {
        $cfg = new Config($modCfgArray['default']);
        $ns = $cfg->get('namespace');

        // If this class can't be loaded, enable the autoload using the composer class loader.
        if(! empty($ns)){
            // Full directory of the module to be initialized.
            $dir = $cfg->get('directory');
            $parentDir = $this->config->get('directory');
            $parentModulesDir = $this->config->get('modulesDir');
            if(preg_match('/^\//', $dir) > 0){
                ;
            } else if(preg_match('/^\//', $parentModulesDir) > 0){
                $dir = $parentModulesDir . DIRECTORY_SEPARATOR .  $dir;
            } else {
                $dir = $parentDir . DIRECTORY_SEPARATOR . $parentModulesDir . DIRECTORY_SEPARATOR . $dir;
            }
            $dirReal = realpath($dir) . DIRECTORY_SEPARATOR;

            if(! $dirReal){
                throw new \Exception("The directory $dir of the child module does not exists or is not readable.");
            }

            // Override the current module 'directory' value
            $cfg->set('directory', $dir);

            // Add the namespacec to the  composer ClassLoader
            $loader = new ClassLoader();
            $loader->add($ns, $dir);
            $loader->register();
            $loader->setUseIncludePath(true);
        }

        if($cn = $cfg->get('moduleClassName')){
            $mClass = $ns.'\\'.$cn;
        } else {
            // Use the default moduleClassName. See the top of this file.
            $mClass = $this->config->get('moduleClassName');
        }

        // Add others options sets, if provided
        foreach($modCfgArray as $k => $optSet){
            if($k == 'default') {
                continue;
            }
            $cfg->set($optSet, null, $k);
        }

        // Instance the module.
        $mod = new $mClass($cfg, $this, $this->injections);
        return $this->children[$modId] =  $mod;
    }

    //Initialize the module
    public function init($uri = null)
    {
        $req = new Request($uri);
        $this->baseUrl = ($this->parent and $this->parent->baseUrl) ?
            $this->parent->baseUrl . $this->config->get('route') :
            $this->config->get('route');


        // Load the module routes.
        $this->addModuleRoutes();

        // This allow the autoloading of submodules with the config option 'autoload' set to true.
        foreach($this->modulesToLoad as $mCfgArray){
            $m = $this->loadModule($mCfgArray, $mCfgArray['default']['id']);

            // Check if the request match a public static file inside the module.
            if(method_exists($m, 'getFile') && array_key_exists('route', $mCfgArray['default'])){
                $rgx = '/^(\/*)?'.preg_quote($mCfgArray['default']['route']).'(\/*)?/';
                $fileRequest = preg_replace($rgx, '', $req->uri);
                $f = $m->getFile($fileRequest);
                if($f){
                    return $f;
                }
            }
        }
        return $this->router->check($req->uri);
    }


    /**
     * Call the module.
     */
    public function callModule($modCfgArray)
    {
        // The next request wont have full original request, only the remaining part.
        $regExpReq = "/\/?".str_replace('/', '\/', $modCfgArray['default']['route'])."\/?/";
        $reqUri = preg_replace($regExpReq, '/', $this->router->request);

        if (array_key_exists($modCfgArray['default']['id'], $this->children)) {
            $mod = $this->children[$modCfgArray['default']['id']];
        } else {
            $mod = $this->loadModule($modCfgArray, $modCfgArray['default']['id']);
        }

        $mod->init($reqUri);
    }


    public function getFile($uri)
    {
        $filePath = static::getPath($this->config->get('directory'),
            $this->config->get('publicDir')) .
            DIRECTORY_SEPARATOR . $uri;
        if(is_readable($filePath)){
            $fi = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $fi->file($filePath);
            header('Content-type: '.$mimeType);
            return readfile($filePath);
        } else {
            return false;
        }
    }

    public function getPath($optBaseDir, $path)
    {
        if(preg_match('/^\//', $path) === 0){
            $path = $optBaseDir . DIRECTORY_SEPARATOR . $path;
        }
        $path = realpath($path);
        return $path;
    }
}