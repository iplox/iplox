<?php

    namespace Iplox\Event {
        
        
        class EventHandler{
            protected $goHandlers;
            
            //Permite asignar métodos de captura para cuando se ejecute el método go.
            public function addGoHandler($fn, $params = array(), $toTheEnd=true){
                if(is_callable($fn)){
                    if(isset($toTheEnd) && $toTheEnd==true)
                        array_push($this->goHandlers, array('fn'=>$fn, 'params'=>$params));
                    else
                        array_unshift($this->goHandlers, array('fn'=>$fn, 'params'=>$params));
                }
            }

            //Método auxiliar para ejecutar en los métodos de captura al resolver las rutas. 
            public function go($extraParams=null){
                foreach($this->goHandlers as $k => $h){
                    $h['params'] = array_merge($h['params'], array($extraParams));
                    call_user_func_array($h['fn'], $h['params']);
                }
            }
        
}