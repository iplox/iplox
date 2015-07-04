<?php

namespace Iplox;

use Composer\Autoload\ClassLoader;

class BasicModule extends ModuleAbstract {

    protected $router;

    public function __construct($cfg)
    {
        //Add options for a General set.
        $cfg->addKnownOptions([
            // General options.
            'defaultContentType' => 'application/json',

            // Submodules options
            'modules' => [],
            'modulesDir' => 'Modules',
            'moduleClassName' => __CLASS__,
        ]);

        //Add options for a Db (database) set.
        $cfg->addKnownOptions('Db', [
            'provider' => 'mysql',
            'username' => 'root',
            'password' => '',
            'hostname' => 'localhost',
            'port' => '3386',
            'dbname' => 'IploxApp',
        ]);

        parent::__construct($cfg);

        $this->router = new Router();

        // Load the module routes.
        $this->addModuleRoutes();
    }

    public function __get($name)
    {
        if($name === 'router'){
            return $this->router;
        }
        return parent::__get($name);

    }
    // If it has submodules, add the routes.
    protected function addModuleRoutes()
    {
        if(empty($modules = $this->config->modules)){
            return 0;
        }
        $modCfg = [];
        $routes = [];
        foreach($modules as $m){
            $modCfg[$m['route']] = $m;
            $routes[$m['route']] = function () use (&$modCfg) {
                call_user_func([$this, 'callModule'], $modCfg[$this->router->route]);
            };
        }
        $this->router->appendRoutes($routes);
    }

    //Initialize the module
    public function init($uri)
    {
        return $this->router->check($uri);
    }


    /**
     * Call the module.
     */
    public function callModule($subModCfg)
    {
        // The next request wont have full original request, only the remaining part.
        $regExpReq = "/\/?".str_replace('/', '\/', $subModCfg['route'])."\/?/";
        $reqUri = preg_replace($regExpReq, '/', $this->router->request);

        $cfg = new Config($subModCfg);
        $ns = $cfg->get('namespace');

        // If this class can't be loaded, enable the autoload using the composer class loader.
        if(! empty($ns)){
            // Full directory of the module to be initialized.
            $dir = $this->config->get('directory') . DIRECTORY_SEPARATOR .
                $this->config->get('modulesDir') . DIRECTORY_SEPARATOR .  $cfg->get('directory');

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

        $mod = new $mClass($cfg);
        $mod->init($reqUri);
    }
}