<?php

namespace Iplox\Bundles;
use \Smarty;
use Iplox\Mvc\View;
use Iplox\Config as Cfg;

class SmartyBundle extends View {
   public $viewEngine;
   protected static $singleton;
   protected $base;
   protected function __construct(){}
   protected function __clone(){}
   
   public static function setup(){
      $singleton = static::getSingleton();
      static::$singleton->viewEngine = new Smarty();
      
      $viewCfg = Cfg::get('Smarty');
      $gralCfg = Cfg::get('General');
      
      $base = Cfg::getAppDir().DIRECTORY_SEPARATOR.$gralCfg::VIEWS_DIR.DIRECTORY_SEPARATOR;

      if($viewCfg::TEMPLATE_DIR) {
         static::$singleton->setTemplateDir($base.$viewCfg::TEMPLATE_DIR);
      }
      
      if($viewCfg::CACHE_DIR) {
         static::$singleton->setCacheDir($base.$viewCfg::CACHE_DIR);
      }
      
      if($viewCfg::COMPILE_DIR) {
         static::$singleton->setCompileDir($base.$viewCfg::COMPILE_DIR);
      }
      
      if($viewCfg::PLUGINS_DIR) {
         static::$singleton->addPluginsDir($base.$viewCfg::PLUGINS_DIR);
      }
      
      static::$singleton->base = $base;
   }
     
   public static function getSingleton(){
      //Si no existe el singleton se crea y se devuelve.
      if(!isset(static::$singleton)) {
         static::$singleton = new static();
         return static::$singleton;
      } else {
         return static::$singleton;
      }
   }
     
   public function display($templateFile) {
      $this->viewEngine->display($templateFile);
   }
   
   public function assign($arrOrVarName, $arr=null) {
      $this->viewEngine->assign($arrOrVarName, $arr);
   }
   
   public function setTemplateDir($templateDir){
      $this->viewEngine->setTemplateDir($templateDir);
      return $this;
   }
   
   public function setCacheDir($cacheDir){
      $this->viewEngine->setCacheDir($cacheDir);
      return $this;
   }
   
   public function setCompileDir($compileDir){
      $this->viewEngine->setCompileDir($compileDir);
      return $this;
   }
   
   public function addPluginsDir($pluginsDir){
      $this->viewEngine->addPluginsDir($pluginsDir);
      return $this;
   }

   public function setPluginsDir($pluginsDirs){
      $this->viewEngine->setPluginsDir($pluginsDirs);
      return $this;
   }
}