<?php

namespace Zettr\Handler\Magento;

/**
 * Parameters
 *
 * - user_type
 * - resource_id
 * - operation
 *
 * - value: allowed_attributes
 *
 * @author Fabrizio Branca
 */
class Api2AclAttribute extends AbstractDatabase
{


    /**
     * Protected method that actually applies the settings. This method is implemented in the inheriting classes and
     * called from ->apply
     *
     * @throws \Exception
     * @return bool
     */
    protected function _apply()
    {
        $this->_checkIfTableExists('api2_acl_attribute');

        $user_type = $this->param1;
        $resource_id = $this->param2;
        $operation = $this->param3;


        if (empty($user_type)) {
            throw new \Exception("No user_type given");
        }
        if (!in_array($user_type, array('admin', 'customer', 'guest', '%'))) {
            throw new \Exception("Invalid user_type. Must be 'admin', 'customer', or 'guest'");
        }
        if (empty($resource_id)) {
            throw new \Exception("No resourceId given");
        }
        if ($resource_id === 'all' && !empty($operation)) {
            throw new \Exception("No operation allowed if resourceId is 'all'");
        }
        if ($resource_id === 'all') {
            if ($this->value !== '--insert--' && $this->value !== '--delete--') {
                throw new \Exception("When resourceId is 'all' the value must be '--delete--' or '--insert--'");
            }
            $operation = null;
            $this->value = null;
        }

        if (strtolower(trim($this->value)) == '--delete--') {
            $action = AbstractDatabase::ACTION_DELETE;
        } else {
            $action = AbstractDatabase::ACTION_INSERT;
        }

        $sqlParameters = array(
            ':user_type' => $user_type,
            ':resource_id' => $resource_id,
            ':operation' => $operation
        );
        if ($action == AbstractDatabase::ACTION_DELETE) {
            $query = 'DELETE FROM `' . $this->_tablePrefix . 'api2_acl_attribute` WHERE IFNULL(`user_type`, \'\') LIKE :user_type AND IFNULL(`resource_id`, \'\') LIKE :resource_id AND IFNULL(`operation`, \'\') LIKE :operation';
            $this->_processDelete($query, $sqlParameters);
        } else {
            // check if row exists
            $query = 'SELECT `allowed_attributes` FROM `' . $this->_tablePrefix . 'api2_acl_attribute` WHERE IFNULL(`user_type`, \'\') LIKE :user_type AND IFNULL(`resource_id`, \'\') LIKE :resource_id AND IFNULL(`operation`, \'\') LIKE :operation';
            $firstRow = $this->_getFirstRow($query, $sqlParameters);
            if ($firstRow) {
                if ($firstRow['allowed_attributes'] === $this->value) {
                    $this->addMessage(new Message('Attribute is already in place. Skipping.'), Message::SKIPPED);
                } else {
                    if ($resource_id === 'all') {
                        unset($sqlParameters[':operation']);
                        $query = 'UPDATE `' . $this->_tablePrefix . 'api2_acl_attribute` SET `allowed_attributes` = NULL, `operation` = NULL WHERE IFNULL(`user_type`, \'\') LIKE :user_type AND IFNULL(`resource_id`, \'\')';

                    } else {
                        $sqlParameters[':allowed_attributes'] = $this->value;
                        $query = 'UPDATE `' . $this->_tablePrefix . 'api2_acl_attribute` SET `allowed_attributes` = :allowed_attributes WHERE IFNULL(`user_type`, \'\') LIKE :user_type AND IFNULL(`resource_id`, \'\') LIKE :resource_id AND IFNULL(`operation`, \'\') LIKE :operation';
                    }
                    $this->_processUpdate($query, $sqlParameters);
                }
            } else {
                if ($resource_id === 'all') {
                    unset($sqlParameters[':operation']);
                    $query = 'INSERT INTO `' . $this->_tablePrefix . 'api2_acl_attribute` (`user_type`, `resource_id`) VALUES (:user_type, :resource_id)';
                } else {
                    $sqlParameters[':allowed_attributes'] = $this->value;
                    $query = 'INSERT INTO `' . $this->_tablePrefix . 'api2_acl_attribute` (`user_type`, `resource_id`, `operation`, `allowed_attributes`) VALUES (:user_type, :resource_id, :operation, :allowed_attributes)';
                }
                $this->_processInsert($query, $sqlParameters);
            }
        }
        $this->destroyDb();

        return true;
    }

    /**
     * Look up role id for a given role name
     *
     * @param $code
     * @return mixed
     * @throws \Exception
     */
    protected function _getRoleIdFromName($roleName)
    {
        $results = $this->_getAllRows(
            "SELECT `entity_id` FROM `{$this->_tablePrefix}api2_acl_role` WHERE `role_name` LIKE :role_name",
            array(':role_name' => $roleName)
        );

        if (count($results) === 0) {
            throw new \Exception("Could not find a role for name '$roleName'");
        } elseif (count($results) > 1) {
            throw new \Exception("Found more than one role for name '$roleName'");
        }

        $result = end($results);

        return $result['entity_id'];
    }

}
