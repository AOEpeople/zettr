<?php

namespace Zettr\Handler\Magento;

use Zettr\Message;

/**
 * Parameters
 *
 * - username
 * - email
 * - role name
 *
 * @author Fabrizio Branca
 */
class AdminUserActivate extends AbstractDatabase
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
        $this->_checkIfTableExists('admin_user');

        $username = $this->param1 ? $this->param1 : '%';
        $email = $this->param2 ? $this->param2 : '%';
        $rolename = $this->param3 ? $this->param3 : '%';
        $value = $this->value ? 1 : 0;

        $sqlParameters = array(
            ':email' => $email,
            ':username' => $username,
            ':rolename' => $rolename
        );

        $selectQuery = '
            select
              u.user_id as user_id,
              u.email as email,
              u.username as username,
              r.role_name as role_name,
              u.is_active as is_active
            from %1$sadmin_user as u
              join %1$sadmin_role as mm on (u.user_id = mm.user_id)
              join %1$sadmin_role as r on (mm.parent_id = r.role_id)
            where u.username LIKE :username
              and u.email LIKE :email
              and r.role_name LIKE :rolename';
        $query = sprintf($selectQuery, $this->_tablePrefix);

        $udpdateQuery = 'UPDATE `%1$sadmin_user` SET is_active = :is_active WHERE user_id = :user_id';
        $udpdateQuery = sprintf($udpdateQuery, $this->_tablePrefix);

        $rows = $this->_getAllRows($query, $sqlParameters);

        foreach ($rows as $row) { /* @var array $row */
            if ($row['is_active'] == $value) {
                $this->addMessage(
                    new Message(sprintf(
                        'Value "%s" is already in place for user "%s" (email: %s, role: %s)',
                        $value,
                        $row['username'],
                        $row['email'],
                        $row['role_name']),
                        Message::SKIPPED)
                );
            } else {
                $sqlParameters = array(
                    ':user_id' => $row['user_id'],
                    ':is_active' => $value
                );
                $this->_processUpdate($udpdateQuery, $sqlParameters, null, false);
                $this->addMessage(
                    new Message(sprintf(
                        'Updating value to "%s" for user "%s" (email: %s, role: %s)',
                        $value,
                        $row['username'],
                        $row['email'],
                        $row['role_name']),
                    Message::OK)
                );
            }
        }

        $this->destroyDb();

        return true;
    }

}
