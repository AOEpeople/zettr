<?php

namespace Zettr\Handler\Magento;

use Zettr\Message;

/**
 * Parameters
 *
 * - scope
 * - scopeId
 * - path
 */
class CoreConfigData extends AbstractDatabase
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
        $this->_checkIfTableExists('core_config_data');

        $scope   = $this->param1;
        $scopeId = $this->param2;
        $path    = $this->param3;

        $sqlParameters       = $this->_getSqlParameters($scope, $scopeId, $path);
        $containsPlaceholder = $this->_containsPlaceholder($sqlParameters);
        $action              = self::ACTION_NO_ACTION;

        if (strtolower(trim($this->value)) == '--delete--') {
            $action = self::ACTION_DELETE;
        } else {
            $query = 'SELECT `value` FROM `' . $this->_tablePrefix . 'core_config_data` WHERE `scope` LIKE :scope AND `scope_id` LIKE :scopeId AND `path` LIKE :path';
            $firstRow = $this->_getFirstRow($query, $sqlParameters);

            if ($containsPlaceholder) {
                // scope, scope_id or path contains '%' char - we can't build an insert query, only update is possible
                if ($firstRow === false) {
                    $this->addMessage(
                        new Message('Trying to update using placeholders but no rows found in the db', Message::SKIPPED)
                    );
                } else {
                    $action = self::ACTION_UPDATE;
                }
            } else {
                if ($firstRow === false) {
                     $action = self::ACTION_INSERT;
                } elseif ($firstRow['value'] == $this->value) {
                    $this->addMessage(
                        new Message(sprintf('Value "%s" is already in place. Skipping.', $firstRow['value']), Message::SKIPPED)
                    );
                } else {
                     $action = self::ACTION_UPDATE;
                }
            }
        }

        switch ($action) {
            case self::ACTION_DELETE:
                $query = 'DELETE FROM `' . $this->_tablePrefix . 'core_config_data` WHERE `scope` LIKE :scope AND `scope_id` LIKE :scopeId AND `path` LIKE :path';
                $this->_processDelete($query, $sqlParameters);
                break;
            case self::ACTION_INSERT:
                $sqlParameters[':value'] = $this->value;
                $query = 'INSERT INTO `' . $this->_tablePrefix . 'core_config_data` (`scope`, `scope_id`, `path`, value) VALUES (:scope, :scopeId, :path, :value)';
                $this->_processInsert($query, $sqlParameters);
                break;
            case self::ACTION_UPDATE:
                $sqlParameters[':value'] = $this->value;
                $query = 'UPDATE `' . $this->_tablePrefix . 'core_config_data` SET `value` = :value WHERE `scope` LIKE :scope AND `scope_id` LIKE :scopeId AND `path` LIKE :path';
                $this->_processUpdate($query, $sqlParameters, $firstRow['value']);
                break;
            case self::ACTION_NO_ACTION;
            default:
                break;
        }

        $this->destroyDb();

        return true;
    }

    /**
     * Protected method that actually extracts the settings. This method is implemented in the inheriting classes and
     * called from ->extract and only echos constructed csv
     */
    protected function _extract()
    {
        $this->_checkIfTableExists('core_config_data');

        $scope   = $this->param1;
        $scopeId = $this->param2;
        $path    = $this->param3;

        $sqlParameters = $this->_getSqlParameters($scope, $scopeId, $path);

        $query = 'SELECT scope, scope_id, path, value FROM `' . $this->_tablePrefix
                 . 'core_config_data` WHERE `scope` LIKE :scope AND `scope_id` LIKE :scopeId AND `path` LIKE :path';

        return $this->_outputQuery($query, $sqlParameters);
    }

    /**
     * Constructs the sql parameters
     *
     * @param string $scope
     * @param string $scopeId
     * @param string $path
     * @return array
     * @throws \Exception
     */
    protected function _getSqlParameters($scope, $scopeId, $path)
    {
        if (empty($scope)) {
            throw new \Exception("No scope found");
        }
        if (is_null($scopeId)) {
            throw new \Exception("No scopeId found");
        }
        if (empty($path)) {
            throw new \Exception("No path found");
        }

        if (!in_array($scope, array('default', 'stores', 'websites', '%'))) {
            throw new \Exception("Scope must be 'default', 'stores', 'websites', or '%'");
        }

        if ($scope == 'default') {
            $scopeId = 0;
        }

        if ($scope == 'stores' && !is_numeric($scopeId) && $scopeId !== '%') {
            // do a store code lookup
            $code    = $scopeId;
            $scopeId = $this->_getStoreIdFromCode($code);
            $this->addMessage(new Message("Found store id '$scopeId' for code '$code'", Message::INFO));
        }

        if ($scope == 'websites' && !is_numeric($scopeId) && $scopeId !== '%') {
            // do a website code lookup
            $code    = $scopeId;
            $scopeId = $this->_getWebsiteIdFromCode($code);
            $this->addMessage(new Message("Found website id '$scopeId' for code '$code'", Message::INFO));
        }

        return array(
            ':scope'   => $scope,
            ':scopeId' => $scopeId,
            ':path'    => $path
        );
    }
}
