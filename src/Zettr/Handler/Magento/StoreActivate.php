<?php

namespace Zettr\Handler\Magento;

use Zettr\Message;

/**
 * @author Fabrizio Branca
 */

/**
 * Parameters:
 * - store (Id or Code)
 * - not used
 * - not used
 */
class StoreActivate extends AbstractDatabase
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
        $this->_checkIfTableExists('core_store');
        $v = $this->_tablePrefix . 'core_store';

        $store = $this->param1;
        if (!is_numeric($store)) {
            $code = $store;
            $store = $this->_getStoreIdFromCode($code);
            $this->addMessage(new Message("Found store id '$store' for code '$code'", Message::INFO));
        }

        $value = filter_var($this->value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

        $this->_processUpdate(
            "UPDATE `{$v}` SET is_active = :value WHERE `store_id` = :store_id",
            array('store_id' => $store, 'value' => $this->value),
            $value['html_value']
        );
        return true;
    }
}
