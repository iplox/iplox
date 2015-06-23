<?php

namespace Iplox;

use Composer\Autoload\ClassLoader;

class BasicModule extends ModuleAbstract {

    public function __construct($cfg)
    {
        parent::__construct($cfg);

        //Add options for a General set.
        $this->config->addKnownOptions([
            // Submodules options
            'modules' => [],
            'modulesDir' => 'Modules',
            'modulesClassName' => 'Module',

            // Reserved options for "future" use
            'bundlesDir' => 'Bundles',
        ]);

        //Add options for a Db (database) set.
        $this->config->addKnownOptions('Db', [
            'provider' => 'mysql',
            'username' => 'root',
            'password' => '',
            'hostname' => 'localhost',
            'port' => '3386',
            'dbname' => 'IploxApp',
        ]);

        // Load the module routes.
        $this->addModuleRoutes();
    }

    // If it has submodules, add the routes.
    protected function addModuleRoutes()
    {
        $modCfg = [];
        $routes = [];
        foreach($this->config->modules as $m){
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
        // Let's do it!
        $this->router->check($uri);
    }


    /**
     * Call the module.
     */
    public function callModule($subModCfg)
    {
        // The next request wont have full original request, only the remaining part.
        $regExpReq = "/\/?".str_replace('/', '\/', $subModCfg['route'])."\/?/";
        $reqUri = preg_replace($regExpReq, '/', $this->router->request);

        if(array_key_exists('directory', $subModCfg) && array_key_exists('namespace', $subModCfg)) {
            $loader = new ClassLoader();
            $loader->add($subModCfg['namespace'], $subModCfg['directory']);
            $loader->register();
            $loader->setUseIncludePath(true);

            if(array_key_exists('className', $subModCfg)){
                $mClass = $subModCfg['namespace'].'\\'.$subModCfg['className'];
            } else {
                $mClass = $subModCfg['namespace'].'\\'.$this->config->modulesClassName;
            }

            $cfg = new Config(
                $subModCfg['directory'],
                array_key_exists('namespace', $subModCfg) ? $subModCfg['namespace'] : '',
                array_key_exists('options', $subModCfg) ? $subModCfg['options'] : [],
                $this->config->env
            );
            $mod = new $mClass($cfg);
            $mod->init($reqUri);
        } else {
            throw new \Exception("The 'namespace' and/or 'directory' configuration options ".$subModCfg['name']." where not found for the ".$subModCfg['name']." module.");
        }
    }
}