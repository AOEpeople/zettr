<?php

namespace Zettr\Handler\Magento;

use Zettr\Message;

/**
 * @author Lee Saferite <lee.saferite@aoe.com>
 */

/**
 * Parameters
 *
 * - code (variable code)
 * - store (Id or Code)
 * - type (html or text
 */
class CoreCustomVariable extends AbstractDatabase
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
        $this->_checkIfTableExists('core_variable');
        $this->_checkIfTableExists('core_variable_value');

        $v = $this->_tablePrefix . 'core_variable';
        $vv = $this->_tablePrefix . 'core_variable_value';

        $params = $this->_getSqlParameters($this->param1, $this->param2, $this->param3);

        $query = "SELECT variable_id FROM `{$v}` WHERE `code` = ?";
        $variable = $this->_getFirstRow($query, array($params['code']));
        if ($variable === false) {
            $this->getDbConnection()
                ->prepare("INSERT INTO `{$v}` (`code`, `name`) VALUES (?, ?)")
                ->execute(array($params['code'], $params['code']));

            $variable = $this->_getFirstRow($query, array($params['code']));
            if ($variable === false) {
                throw new \Exception("Could not create variable record");
            }
        }
        $params['variable'] = intval($variable['variable_id']);

        $query = "SELECT value_id, plain_value, html_value FROM `{$vv}` WHERE `variable_id` = :variable AND `store_id` = :store";
        $value = $this->_getFirstRow($query, array('variable' => $params['variable'], 'store' => $params['store']));

        if ($value !== false) {
            if ($params['type'] === 'text') {
                if ($value['plain_value'] == $this->value) {
                    $this->addMessage(
                        new Message(sprintf('Value "%s" is already in place. Skipping.', $value['plain_value']), Message::SKIPPED)
                    );
                } else {
                    $this->_processUpdate(
                        "UPDATE `{$vv}` SET plain_value = :value WHERE `value_id` = :id",
                        array('id' => $value['value_id'], 'value' => $this->value),
                        $value['plain_value']
                    );
                }
            } else {
                if ($value['html_value'] == $this->value) {
                    $this->addMessage(
                        new Message(sprintf('Value "%s" is already in place. Skipping.', $value['html_value']), Message::SKIPPED)
                    );
                } else {
                    $this->_processUpdate(
                        "UPDATE `{$vv}` SET html_value = :value WHERE `value_id` = :id",
                        array('id' => $value['value_id'], 'value' => $this->value),
                        $value['html_value']
                    );
                }
            }
        } else {
            $this->_processInsert(
                "INSERT INTO `{$vv}` (variable_id, store_id, plain_value, html_value) VALUES (?, ?, ?, ?)",
                array(
                    $params['variable'],
                    $params['store'],
                    ($params['type'] === 'text' ? $this->value : ''),
                    ($params['type'] === 'html' ? $this->value : ''),
                )
            );
        }

        return true;
    }

    /**
     * Protected method that actually extracts the settings. This method is implemented in the inheriting classes and
     * called from ->extract and only echos constructed csv
     */
    protected function _extract()
    {
        $this->_checkIfTableExists('core_variable');
        $this->_checkIfTableExists('core_variable_value');

        $v = $this->_tablePrefix . 'core_variable';
        $vv = $this->_tablePrefix . 'core_variable_value';

        $code = trim($this->param1);
        $store = trim($this->param2);
        $type = trim($this->param3);

        $allCodes = false;
        $allStores = false;
        $allTypes = false;

        if ($code === '') {
            $allCodes = true;
            $code = 'DUMMY';
        }

        if ($store === '') {
            $allStores = true;
            $store = 0;
        }

        if ($type === '') {
            $allTypes = true;
            $type = 'text';
        }

        $params = $this->_getSqlParameters($code, $store, $type);

        $type = $params['type'];
        unset($params['type']);

        $where = array();

        if ($allCodes) {
            unset($params['code']);
        } else {
            $where[] = 'v.`code` = :code';
        }

        if ($allStores) {
            unset($params['store']);
        } else {
            $where[] = 'vv.`store_id` = :store';
        }

        if (empty($where)) {
            $where = '';
        } else {
            $where = ' WHERE ' . implode(' AND ', $where);
        }

        $output = '';

        if ($type === 'text' || $allTypes) {
            $query = "SELECT v.code, vv.store_id, 'text', vv.plain_value FROM `{$v}` AS v JOIN `{$vv}` AS vv ON v.variable_id = vv.variable_id {$where}";
            $output .= $this->_outputQuery($query, $params);
        }

        if ($type === 'html' || $allTypes) {
            $query = "SELECT v.code, vv.store_id, 'html', vv.html_value FROM `{$v}` AS v JOIN `{$vv}` AS vv ON v.variable_id = vv.variable_id {$where}";
            $output .= $this->_outputQuery($query, $params);
        }

        return $output;
    }

    /**
     * Constructs the sql parameters
     *
     * @param string     $code
     * @param int|string $store
     * @param string     $type
     *
     * @return array
     * @throws \Exception
     */
    protected function _getSqlParameters($code, $store, $type)
    {
        $code = trim($code);
        $store = trim($store);
        $type = trim($type);

        if (empty($code)) {
            throw new \Exception("No code found");
        }

        if (is_null($store)) {
            throw new \Exception("No store found");
        }
        if (!is_numeric($store)) {
            $code = $store;
            $store = $this->_getStoreIdFromCode($code);
            $this->addMessage(new Message("Found store id '$store' for code '$code'", Message::INFO));
        }

        if (empty($type)) {
            throw new \Exception("No type found");
        }
        if ($type !== 'text' && $type !== 'html') {
            throw new \Exception("Invalid type '{$type}' found");
        }

        return array('code' => $code, 'store' => $store, 'type' => $type);
    }
}
