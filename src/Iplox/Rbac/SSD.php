<?php  
	namespace Iplox\Rbac {
		class SSD {
			/***** Administrative Functions *****/
			//
			public function createSSDSet(){
			
			}
			//
			public function deleteSSDSet(){
			
			}
			//
			public function addSSDRoleMember(){
			
			}
			//
			public function deleteSSDRoleMember(){
			
			}
			//
			public function setSSDCardinality(){
			
			}
			
			/***** System Functionality *****/
			
			//Create a session.
			public function createSession($userId, $roles){}
			//
			public function addActiveRole($role){}
			//
			public function dropActiveRole($role){}
			//
			public function checkAccess($prms){}
		}
	}
?>
