<?php

namespace Iplox\Mvc {

    use \Exception;

    abstract class Model {
        /*         * * Campos de clase ** */

        protected $mir = null;

        /*         * * Campos de instancia ** */

        /*         * * Métodos de clase ** */

        /// Las entidades cuyas propiedades son se le asignaran al modelo.
        public static function setEntities($entitiesNames) {
//            echo $entitiesNames[0];
        }

        ///Crear relación ManyToMany con la entidad $entityName mediante $linkEntity.
        public static function linkManyToMany($entityName, $linkEntity) {
            
        }

        ///Crear relación OneToMany con la entidad $entityName.
        public static function linkOneToMany($entityName) {
            
        }

        public static function get() {

            //Create a new instance of ModelInternalRequest 
        }

        ///
        public static function inc() {
            
        }

        ///
        public static function toArray() {
            
        }

        ///
        public static function toJson() {
            
        }

        ///
        public static function toXml() {
            
        }

        ///
        public static function x() {

            //Destroy the ModelInternalRequest instance
            static::$mir->pdo->execute();
        }

        /*         * * Métodos de instancia ** */
    }

}

?>
