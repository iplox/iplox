<?php

namespace Iplox\Mvc {
   
   abstract class View {
      public $engine;
      
      public abstract function display($templateDir);
      public abstract function assign($arrOrVarName, $arr=array());
      public abstract function setTemplateDir($templateDir);
      public abstract function setCacheDir($cacheDir);
      public abstract function setCompileDir($cacheDir);
      public abstract function addPluginsDir($pluginsDir);
   }
}
