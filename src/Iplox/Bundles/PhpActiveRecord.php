<?php

namespace Iplox\Config;
use Iplox\Config as Cfg;
use ActiveRecord\Config as ActiveCfg;

class  PhpActiveRecordBundle {
    public function setup() {
        $db = Cfg::get('Db');
        $gral = Cfg::get('General');
        
        $connections = array();
        $connections[strtolower(Cfg::getEnv())]  = $db::USER.'://'.$db::PASSWORD.':'.$db::PROVIDER.'@'.$db::NAME;
        
        ActiveCfg::initialize(function($cfg) use (&$connections){
            $cfg->set_model_directory(Cfg::getAppDir().DIRECTORY_SEPARATOR.$gral::MODELS_DIR);
            $cfg->set_connections($connections);
        });
    }
}
