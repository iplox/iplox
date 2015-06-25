<?php

namespace Iplox\Mvc;
use \Smarty;

class TemplateController extends Controller {
    protected $view;
    protected $data = [];
    protected $defaultTemplate = 'index';
    protected $defaultExtention = 'tpl';

    public function __construct($cfg){
        $this->view = new Smarty();

        $baseDir = $cfg->get('directory') . DIRECTORY_SEPARATOR . $cfg->get('viewsDir') . DIRECTORY_SEPARATOR;
        $cfg->addKnownOptions('smarty', [
            'pluginsDir' => $baseDir .'plugins',
            'templateDir' => $baseDir .'templates',
            'cacheDir' => $baseDir .'cache',
            'setCompileDir' => $baseDir .'compiles',
        ]);
        // Load the entire smarty optionSet 
        $smartyCfg = $cfg->getSet('smarty');

        // Set the directories for smarty files.
        $this->view->setPluginsDir($smartyCfg['pluginsDir']);
        $this->view->setTemplateDir($smartyCfg['templateDir']);
        $this->view->setCacheDir($smartyCfg['cacheDir']);
        $this->view->setCompileDir($smartyCfg['setCompileDir']);

        //
        $this->assign = [];
    }

    public function show($data=null, $tpl=null){
        if(! isset($this->vars)){
            $this->vars = [];
        }
        if(! isset($data)){
            $data = [];
        }

        $viewData = array_merge_recursive($this->vars, $data);

        $this->view->assign($viewData); 
        $tpl = isset($tpl) ? $tpl : $this->defaultTemplate;

        // Add a .tpl or any specified extension if none is provided.
        $rg = "/\\.$this->defaultExtention$/";
        $tpl = (preg_match($rg, $tpl) > 0) ? $tpl : $tpl . '.' . $this->defaultExtention;

        $this->view->display($tpl);

    }

}
