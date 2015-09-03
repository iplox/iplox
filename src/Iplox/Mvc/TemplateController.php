<?php

namespace Iplox\Mvc;
use \Smarty;
use Iplox\BaseController;
use Iplox\Config;
use Iplox\AbstractModule;

class TemplateController extends BaseController {
    protected $module;
    protected $view;
    protected $data = [];
    protected $defaultTemplate = 'index';
    protected $defaultExtention = 'tpl';

    public function __construct(Config $cfg, AbstractModule $module = null, $params = null){

        parent::__construct($cfg, $module);

        $this->view = new Smarty();

        $viewBaseDir = $module->getPath($cfg->get('directory'), $cfg->get('viewsDir')) . DIRECTORY_SEPARATOR;
        $cfg->addKnownOptions('smarty', [
            'pluginsDir' => $viewBaseDir .'plugins',
            'templateDir' => $viewBaseDir .'templates',
            'cacheDir' => $viewBaseDir .'cache',
            'compileDir' => $viewBaseDir .'compiles',
        ]);

        // Load the entire smarty optionSet 
        $smartyCfg = $cfg->getSet('smarty');

        // Set the directories for smarty files.
        $this->view->setPluginsDir($module->getPath($viewBaseDir, $smartyCfg['pluginsDir']));
        $this->view->setTemplateDir($module->getPath($viewBaseDir, $smartyCfg['templateDir']));
        $this->view->setCacheDir($module->getPath($viewBaseDir, $smartyCfg['cacheDir']));
        $this->view->setCompileDir($module->getPath($viewBaseDir, $smartyCfg['compileDir']));

        //
        $this->assign = [];
    }

    public function show($tpl, $data=[]){
        if(! isset($this->vars)){
            $this->vars = [];
        }

        $viewData = array_merge_recursive($this->vars, $data);

        $this->view->assign($viewData); 
        $tpl = isset($tpl) ? $tpl : $this->defaultTemplate;

        // Add a .tpl or any specified extension if none is provided.
        $rg = "/\\.$this->defaultExtention$/";
        $tpl = (preg_match($rg, $tpl) > 0) ? $tpl : $tpl . '.' . $this->defaultExtention;

        return $this->view->fetch($tpl);

    }

}
