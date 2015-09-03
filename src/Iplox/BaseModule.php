<?php

namespace Iplox;
use Iplox\Http\Request;
use Iplox\Http\Response;
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
            'contentType' => 'text/html',

            // Submodules options
            'modules' => [],
            'modulesDir' => 'modules',
            'publicDir' => '../public',
            'servePublicFiles' => true,
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

        $cfg->addKnownOptions('routes', array());

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

    //Initialize the module
    public function init($uri = null, $baseUrl = null)
    {
        // The request for this module.
        $req = new Request($uri);

        // Detect the $baseUrl of this module.
        if($baseUrl) {
            $this->baseUrl = $baseUrl;
        } else if($this->parent && $this->parent->baseUrl){
            $route = $this->config->get('route');
            $this->baseUrl = !empty($route) ? $this->parent->baseUrl . $route : $this->parent->baseUrl;
        } else {
            $this->baseUrl = '/';
        }

        // Load modules with autoload option enable.
        $this->autoloadModules();

        // Check if the requested uri match a file in one of the autoloaded modules.
        $this->getFileFromChild($req->uri);

        // Load the module routes.
        $this->addModuleRoutes();

        // Load routes from 'routes' config file.
        $this->loadConfigRoutes();

        $response = $this->router->check($req->uri);

        // This module is set to return the result locally?
        if($this->config->get('return') === true) {
            return $response;
        }

        // If not response was provided, call the not found handler.
        if ($response === false) {
            $response = $this->notFoundHandler($req->uri);
        }

        // If the response isn't a Response instance, then wrap it appropriately.
        if(! ($response instanceof Response)) {
            $response = new Response(empty($response) ? [] : $response, $this->config->get('contentType'));
        }

        // Set header(), echo() the data, exit().
        $response->end();
    }

    /**
     * This allow the autoloading of submodules with the config option 'autoload' set to true.
     * @return int
     * @throws \Exception
     */
    protected function autoloadModules()
    {
        $modules = $this->config->get('modules');
        if(empty($modules)){
            return 0;
        }

        //
        foreach($modules as $idx => $m) {
            $m['default']['idx'] = $idx;
            $this->loadModule($m, $idx);
        }
    }

    protected function loadModule($modCfgArray, $modIdx)
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
        return $this->children[$modIdx] =  $mod;
    }

    /**
     * Check if the request match a public static file inside the module.
     * @return mixed
     */
    public function getFileFromChild($uri)
    {
        // $this->children has a ref of all the loaded children modules.
        foreach($this->children as $m) {
            if (method_exists($m, 'getFile') and $m->config->get('servePublicFiles') == true) {
                $rgx = '/^(\/*)?' . preg_quote($m->config->get('route'), '/') . '(\/*)?/';
                $fileRequest = preg_replace($rgx, '', $uri);
                $f = $m->getFile($fileRequest);
                if ($f) {
                    return $f;
                }
            }
        }
        return false;
    }

    public function getFile($uri)
    {
        $filePath = static::getPath($this->config->get('directory'),
                $this->config->get('publicDir')) .
            DIRECTORY_SEPARATOR . $uri;
        if(is_readable($filePath)){
            $fi = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $fi->file($filePath);
            return new Response(
                readfile($filePath),
                $mimeType
            );
        } else {
            return false;
        }
    }

    public function getPath($optBaseDir, $path)
    {
        if(! (
            preg_match('/^\//', $path) > 0 ||
            (strpos($path, ":") == 1 && preg_match('/^[a-zA-Z]/', $path) > 0)
        )){
            $path = $optBaseDir . DIRECTORY_SEPARATOR . $path;
        }

        $path = realpath($path);
        return $path;
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

            if(!array_key_exists('route', $m['default']) && ! empty($m['default']['route'])) {
                continue;
            }
            $baseRoute = $m['default']['route'];
            $realRoute = preg_replace('/\/*/', '/', $baseRoute . '/{*params}');
            $modCfg[$realRoute] = $m;
            $routes[$m['default']['route']] = function () use (&$modCfg) {
                call_user_func([$this, 'callModule'], $modCfg[$this->router->route]);
            };
        }
        $this->router->prependRoutes($routes);
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

    protected function loadConfigRoutes()
    {
        $routeMaps= $this->config->getSet('routes');
        foreach($routeMaps as $methodRoute => $handler){
            $methodRoute  = trim($methodRoute, " \t");
            $arr = preg_split('/\ /', $methodRoute);
            if(count($arr) == 1){
                $route = $arr[0];
                $method = 'ALL';
            } else if(count($arr) == 2) {
                $method = $arr[0];
                $route = $arr[1];
            }

            $handlerMethod = $this->getValidHandler($handler);

            if($handlerMethod === false){
                throw new \Exception("The $handler mapped to the method-route $methodRoute is not callable.");
            }

            $this->router->prependRoute($method, $route, function() use($handlerMethod){
                return call_user_func($handlerMethod, $this->config, $this);
            });
        }
    }

    public function getValidHandler($handler)
    {
        if (is_callable($handler)) {
            return $handler;
        } elseif(is_string($handler) && preg_match('/^[\w\\\]*->\w*$/', $handler) > 0) {
            $handler = preg_split('/\->/', $handler);
            if(is_callable($handler)){
                return $handler;
            }
        }
        return false;
    }
}