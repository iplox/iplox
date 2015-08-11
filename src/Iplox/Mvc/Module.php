<?php

namespace Iplox\Mvc;
use Iplox\Config;
use Iplox\AbstractModule;
use Iplox\BaseModule;

class Module extends BaseModule
{
    public function __construct(Config $cfg, AbstractModule $parent = null, Array $injections = null)
    {
        parent::__construct($cfg, $parent, $injections);

        // Add the options related to this module.
        $cfg->addKnownOptions([
            'controllerNamespace' => 'Controllers',
            'controllerSuffix' => '',
            'alternativeMethodSuffix' => 'Action',
            'viewsDir' => 'views', 
            'modelSuffix' => '',
            'entitySuffix' => '',
            'notFoundHandler'=> 'notFoundHandler',
            'defaultGlobalHandler' => 'defaultGlobalHandler',
            'defaultController' => 'Index',
            'defaultMethod' => 'index',
            'moduleClassName' => __CLASS__
        ]);

        $cfg->refreshCache();

        //Determine if any of this routes actually exists as an object
        $this->router->addFilters(array(
            ':controller' => function($name, $path) {
                $ns = $this->config->namespace . '\\' .
                    ucwords(preg_replace(['/^\//', '/\//'], ['', '\\'], $path)) .
                    $this->config->controllerNamespace . '\\';
                $class = $ns . ucwords($name) . $this->config->controllerSuffix;
                if (class_exists($class, TRUE)){
                    return true;
                }
                return false;
            },
            ':num'=> function($val){
                if(is_numeric($val)){
                    return true;
                } else {
                    return false;
                }
            }
        ));

        // Filters for the MVC Controller functionality
        $this->router->appendRoutes([
            '/:namespace/:controller/:method/(*params)?'=>  array($this, 'captureNSControllerMethod'),
            '/:namespace/:controller/(*params)?'=>  array($this, 'captureNSController'),
            '/:controller/:method/(*params)?'=>  array($this, 'captureControllerMethod'),
            '/:controller/(*param)?' => array($this, 'captureController'),
            '/(*param)?' => array($this, 'captureAll')
        ]);
    }

    public function captureNSControllerMethod($ns, $controller, $method, $params='') {
        $controllerName =
            $this->config->namespace . '\\' .
            (isset($ns) ? ucwords($ns) . '\\' : '') .
            $this->config->controllerNamespace . '\\' .
            ucwords($controller) . $this->config->controllerSuffix;

        if(class_exists($controllerName)) {
            $inst = new $controllerName($this->config, $this, $this->injections);
            if(method_exists($inst, $method . ucwords($this->router->requestMethod))) {
                call_user_func_array(array($inst, $method . ucwords($this->router->requestMethod)), preg_split('/\/{1}/', $params));
            } else if(method_exists($inst, $method . ucwords($this->config->alternativeMethodSuffix))){
                call_user_func_array(array($inst, $method . ucwords($this->config->alternativeMethodSuffix)), preg_split('/\/{1}/', $params));
            } else {
                throw new \Exception("the method \"$method" .
                    ucwords($this->router->requestMethod)."\" or \"" .
                    $method . ucwords($this->config->alternativeMethodSuffix)."\" was not found in the controller $controllerName.");
            }
            return true;
        } else {
            throw new \Exception("The controller \"$controllerName\" was not found.");
        }
    }

    public function captureNSController($ns, $controller) {
        return $this->captureNSControllerMethod(
            $ns,
            $controller,
            $this->config->defaultMethod
        );
    }

    public function captureControllerMethod($controller, $method, $params='') {
        return $this->captureNSControllerMethod(
            null,
            $controller,
            $method,
            $params
        );
    }

    public function captureController($controller, $params='') {
        return $this->captureNSControllerMethod(
            null,
            $controller,
            $this->config->defaultMethod,
            $params
        );
    }

    public function captureAll($params='') {
        if(empty($params)) {
            if($this->config->defaultController){
                return $this->captureNSControllerMethod(
                    null,
                    $this->config->defaultController,
                    $this->config->defaultMethod,
                    $params
                );
            }
            $handler = [$this, $this->config->defaultGlobalHandler];
            if (is_callable($handler)) {
                return call_user_func($handler);
            }
            throw new \Exception("A \"defaultGlobalHandler\" was not provided or is not valid.");
        } else {
            $handler = $this->config->get('notFoundHandler');
//            $handler = [$this, $this->config->notFoundHandler];
//            if (is_callable($handler)) {
//                return call_user_func($handler, $params);
//            } else
            if(is_string($handler) && preg_match('/^\w*\->\w*$/', $handler) > 0) {
                $parts = preg_split('/\->/', $handler);
                $className = $this->config->get('namespace').$parts[0];
                if(class_exists($className) and is_callable([
                        $inst = new $className($this->config),
                        $parts[1]
                    ])){
                    return call_user_func([$inst, $parts[1]]);
                } else {
                    return $this->notFoundHandler('');
                }
            }
            throw new \Exception("A \"notFoundHandler\" was not provided or is a callable.");
        }
    }

    public function notFoundHandler ($param) {
        $nfclass = __NAMESPACE__.'\\NotFound';
        echo "<h2>Not found requested resource</h2>This is the notFoundHandler method.";
    }

    public function defaultGlobalHandler() {
        echo "<h2>Welcome</h2>This is the globalDefaultHandler method.";
    }
}