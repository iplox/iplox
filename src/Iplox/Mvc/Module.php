<?php

namespace Iplox\Mvc;

use Iplox\BasicModule;

class Module extends BasicModule
{
    public function __construct($cfg)
    {

        parent::__construct($cfg);

        // Add the options related to this module.
        $cfg->addKnownOptions([
            'controllerNamespace' => 'Controllers',
            'controllerSuffix' => 'Controller',
            'alternativeMethodSuffix' => 'Action',
            'modelSuffix' => 'Model',
            'entitySuffix' => 'Entity',
            'notFoundHandler'=> 'notFoundHandler',
            'defaultGlobalHandler' => 'defaultGlobalHandler',
            'defaultController' => 'Index',
            'defaultMethod' => 'index',
            'moduleClassName' => 'Iplox\\Mvc\\Module'
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

    public function init($uri)
    {
        if(empty($uri)) {
            $uri = preg_replace('/\?(.*\=.*)*$/', '', $_SERVER['REQUEST_URI']) ;
            $uri = empty($uri) ? '/' : $uri;
        }
        $this->router->check($uri);
    }

    public function captureNSControllerMethod($ns, $controller, $method, $params='') {
        $controllerName =
            $this->config->namespace . '\\' .
            (isset($ns) ? ucwords($ns) . '\\' : '') .
            $this->config->controllerNamespace . '\\' .
            ucwords($controller) . $this->config->controllerSuffix;

        if(class_exists($controllerName)) {
            $inst = new $controllerName();
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
            $handler = [$this, $this->config->notFoundHandler];
            echo $this->config->notFoundHandler;
            if (is_callable($handler)) {
                return call_user_func($handler, $params);
            }
            throw new \Exception("A \"notFoundHandler\" was not provided or is not valid.");
        }
    }

    public function notFoundHandler ($param) {
        echo "<h2>Not found requested resource</h2>This is the notFoundHandler method.";
    }

    public function defaultGlobalHandler() {
        echo "<h2>Welcome</h2>This is the globalDefaultHandler method.";
    }
}