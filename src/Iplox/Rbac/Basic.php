<?php  

namespace Iplox\Rbac {
    use Exception;
    use ExceptionCode;

    class Basic {
        public function __construct($pdoLink, $userId, $productId){
            if(isset($pdoLink) && $pdoLink instanceof \PDO)
                $this->pdo = $pdoLink;
            else
                throw new Exception('Objeto PDO no válido.', ExceptionCode::NoValidPDO);
            $this->userId = $userId;
            $this->productId = $productId;
        }
		
        public function check($permission){
            $q = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM users u
                INNER JOIN users_roles ur
                        ON ur.user_id = u.id
                INNER JOIN roles r
                        ON r.id = ur.role_id
                INNER JOIN roles_permissions rp
                        ON rp.role_id = r.id
                INNER JOIN permissions p
                        ON p.id = rp.permission_id AND p.product_id = :productId
                WHERE u.id = :userId AND p.name = :permission");
            $q->bindParam(':userId', $this->userId, \PDO::PARAM_INT);
            $q->bindParam(':productId', $this->productId, \PDO::PARAM_INT);
            $q->bindParam(':permission', $permission, \PDO::PARAM_INT);
            $q->execute();
            $v = $q->fetch();

            if($v['count'] > 0)
                return true;
            else
                return false;
        }
		
        /***** Administrative Functions *****/
        //Creation of elements
        //Add a new user. Could be handled differently. The developer must decide how to do it.
        public function addUser($dataOrId){
            return false;
        }
        //Delete a user. Could be handled differently. The developer must decide how to do it.
        public function deleteUser($userId){
            return false;
        }
        //Add a new role.
        public function addRole($roleName){
            $q = $this->pdo->prepare("INSERT INTO rbac_roles('name') VALUES(:roleName)");
            $q->bindParam(':roleName', $roleName, \PDO::PARAM_INT);
            if($q->exec() > 0)
                return true;
            else
                return false;
        }
        //Delete a role.
        public function deleteRole($roleId){
            $q = $this->pdo->prepare("DELETE FROM rbac_roles WHERE id = :roleId");
            $q->bindParam(':roleId', $roleId, \PDO::PARAM_INT);
            if($q->exec() > 0)
                return true;
            else
                return false;
        }

        //
        public function assignUser($userId, $roleId){
            $q = $this->pdo->prepare("INSERT INTO rbac_users_roles('user', 'role') VALUES(:user, :role)");
            $q->bindParam(':userId', $userId, \PDO::PARAM_INT);
            $q->bindParam(':roleId', $roleId, \PDO::PARAM_INT);
            if($q->exec() > 0)
                return true;
            else
                return false;
        }

        //
        public function deassignUser($userId, $roleId){
            $q = $this->pdo->prepare("DELETE FROM rbac_users_roles WHERE user_id=:userId && role_id = :roleId");
            $q->bindParam(':userId', $userId, \PDO::PARAM_INT);
            $q->bindParam(':roleId', $roleId, \PDO::PARAM_INT);
            if($q->exec() > 0)
                return true;
            else
                return false;
        }

        //
        public function grantPermission($roleId, $prmsId){
            $q = $this->pdo->prepare("INSERT INTO rbac_roles_prms(role_id', 'prms_id') VALUES(:roleId, :prmsId)");
            $q->bindParam(':roleId', $roleId, \PDO::PARAM_INT);
            $q->bindParam(':prmsId', $prmsId, \PDO::PARAM_INT);
            if($q->exec() > 0)
                return true;
            else
                return false;
        }

        //
        public function revokePermission($roleId, $prmsId){
            $q = $this->pdo->prepare("DELETE FROM rbac_users_roles WHERE role_id=:roleId && prms_id = :prmsId");
            $q->bindParam(':roleId', $roleId, \PDO::PARAM_INT);
            $q->bindParam(':prmsId', $prmsId, \PDO::PARAM_INT);
            if($q->exec() > 0)
                return true;
            else
                return false;
        }


        /***** Administrative Reviews *****/

        //
        public function assignedUsers($roleId){
            $q = $this->pdo->prepare("
                SELECT u.id, u.name 
                FROM rbac_users u 
                INNER JOIN rbac_users_roles ur
                        ON ur.user_id = u.id
                WHERE ur.role_id = :roleId");
            $q->bindParam(':roleId', $roleId, \PDO::PARAM_INT);
        }
        //
        public function assignedRoles($userId){

        }
        //
        public function rolePermissions($role){

        }
        //
        public function userPermissions($role){

        }
        //
        public function sessionRoles($role){

        }
        //
        public function sessionPermissions($role){

        }
        //
        public function roleOperationsOnObject($role, $objectId){

        }
        //
        public function userOperationsOnObject($userId, $objectId){

        }



        /***** System Functionality *****/

        //Create Sessions.
        public function createSession($userId, $roles){}
        public function addActiveRole($role){}
        public function dropActiveRole($role){}
        public function checkAccess($prms){}

        //
    }
}

?>