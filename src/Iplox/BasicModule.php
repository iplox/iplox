<?php

namespace Iplox;

class BasicModule extends ModuleAbstract {

    public function __construct($cfg)
    {
        parent::__construct($cfg);
        $this->addModuleRoutes();
    }

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
            $loader->add($subModCfg['namespace'], $subModCfg['directory']);
            $loader->register();
            $loader->setUseIncludePath(true);

            if(array_key_exists('className', $subModCfg)){
                $mClass = $subModCfg['namespace'].'\\'.$subModCfg['class'];
            } else {
                $mClass = $subModCfg['namespace'].'\\'.$this->config->modulesClassName;
            }

            $mod = new $mClass(new Config($subModCfg));
            $mod->init($reqUri);
        } else {
            throw new \Exception("The 'namespace' and/or 'directory' configuration options ".$subModCfg['name']." where not found for the ".$subModCfg['name'." module."]);
        }
    }
}