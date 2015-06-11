<?php

namespace Iplox\Mvc;
use Iplox\Bundles\SmartyBundle;

class TemplateController extends Controller {
    protected $view;
    protected $data = [];
    protected $defaultTemplate = 'index';
    protected $defaultExtention = 'tpl';

    public function __construct(){
        $this->view = SmartyBundle::getSingleton();
        $this->assign = [
        ];
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
        $tpl = preg_match($rg, $tpl) > 0
            ? $this->defaultTemplate
            : $this->defaultTemplate . '.' . $this->defaultExtention;

        $this->view->display($tpl);

    }
}
