<?php

namespace Iplox\Mvc;
use Iplox\Mvc\Controller;
use Iplox\Config as Cfg;
use Iplox\Router;

class ModularAppController  {
    protected $router;
    protected $moduleCfgList;
    protected static $singleton;

    // The constructor must be hidden in orther to implement the Singleton pattern accurately.
    protected function __construct(){}

    //Retorna el singleton
    public function getSingleton() {
        if(! isset(static::$singleton)) {
            static::$singleton = new static();
        }
        return static::$singleton;
    }

    /**
     * Initializer method.
     */
    public static function init($req){
        $inst = static::getSingleton();
        //Cfg::setAppDir(__DIR__);
        $inst->moduleCfgList = Cfg::get('Modules');
        $inst->router = $r = new Router;

        foreach($inst->moduleCfgList as $m){
          $routes = [];
          $routes[$m['route']] = function () use (&$m, &$req) {
              call_user_func([__CLASS__, 'callModule'], $m, $req);
          };

          $r->appendRoutes($routes);
        }
        // Let's do it!
        $r->check($req);
    }

    /**
     * Call the module.
     */
    public function callModule($moduleCfg, $originalReq)
    {
        $inst = static::getSingleton();
        $reqUri = preg_replace($inst->router->regex, '', $inst->router->request);
        $appDir = Cfg::getAppDir();

        // The next request wont have full original request, only the remaining part.
        $regExpReq = "/\/?".str_replace('/', '\/', $moduleCfg['route'])."\/?/";
        $newReq = preg_replace($regExpReq, '/', $originalReq);

        if(array_key_exists('directory', $moduleCfg)){
          $loader = new \Composer\Autoload\ClassLoader();
          $mDir = $appDir.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $moduleCfg['directory']);

          // Load a module following SPR-0 loader pattern
          $loader->add($moduleCfg['name'], $mDir);
          $loader->register();
          $loader->setUseIncludePath(true);

          if(false & class_exists($mClass = '\\'.$moduleCfg['name'].'\\Module')){
            call_user_func([$mClass, 'init'], $newReq);
          } else if(is_readable($moduleClass = $mDir.DIRECTORY_SEPARATOR.$moduleCfg['name'].'.php')){
            require $moduleClass;
            call_user_func(['\\'.$moduleCfg['name'], 'init'], $newReq);
          } else {
            throw new \Exception('Not found the module '.$moduleCfg['name'].' in the directory "'.$mDir.'".');
          }
        }
    }
}
