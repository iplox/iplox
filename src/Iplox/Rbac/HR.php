<?php  
    namespace Iplox\Rbac {
        use \PDO;
        use Iplox\Rbac\Exception;
        class HR extends Basic {
            
            public function can($prms, $callback=null, $user=null, $product=null){
                if(!isset($user) || !is_int($user)) $user = $this->userId;
                if(!isset($product) || !is_int($product)) $product = $this->productId;
                try {
                    $q = $this->pdo->prepare("CALL rbac_check_user_prms(:prms, :user, :product)");
                    $q->bindParam(':prms', $prms, PDO::PARAM_STR);
                    $q->bindParam(':user', $user, PDO::PARAM_INT);
                    $q->bindParam(':product', $product, PDO::PARAM_INT);
                    $q->execute();
                }
                catch(Exception $e){
                    throw new $e("Error de base de datos. Mensage: '$e->getMessage()'");
                }
                
                $r = $q->fetch();
                if($r['result'] == true){
                    if(isset($callback) && is_callable($callback)) 
                        return call_user_func_array($callback, array());
                    else 
                        return true; 
                }
                else{
                    if(isset($callback) && is_callable($callback)) 
                        return call_user_func_array($callback, array());
                    else
                        return false;
                }
            }
            
            //Add an inheritance of roles.
            public function addInheritance($parentRoleId, $childRoleId){
                $q = $this->pdo->prepare("INSERT INTO rbac_roles_roles('parent_id', 'child_id') VALUES(:parentRoleId, :childRoleId)");
                $q->bindParam(':parentRoleId', $parentRoleId, PDO::PARAM_INT);
                $q->bindParam(':childRoleId', $childRoleId, PDO::PARAM_INT);
                if($q->exec() > 0)
                    return true;
                else
                    return false;
            }
            //
            public function deleteInheritance($parentRoleId, $childRoleId){
                $q = $this->pdo->prepare("DELETE FROM rbac_roles_roles WHERE parent=:parent && child = :child");
                $q->bindParam(':parentRoleId', $parentRoleId, PDO::PARAM_INT);
                $q->bindParam(':childRoleId', $childRoleId, PDO::PARAM_INT);

                if($q->exec() > 0)
                    return true;
                else
                    return false;
            }
            //Add a role and inmediatly adds a inheritance relation to the specified $parent id;
            public function addAscendant($parentRoleId, $childRoleId){

            }
            //
            public function addDescendant($role, $childRole){

            }
            /***** Review Functions *****/
            //
            public function authorizedUsers($role){

            }
            //
            public function authorizedRoles($userId){

            }


            /***** System Functions *****/
            //Redefined this function.
            public function createSession($userId, $roles){
                
            }

            //Redefined this function
            public function addActiveRole($role){

            }

    }
}
?>
