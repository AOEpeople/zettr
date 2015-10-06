<?php

namespace Zettr\Handler\Magento;

/**
 * Class EavEntityStore
 * Set config data in eav_entity_store
 *
 * @category Magento
 * @package  Est_Handler
 * @license  none none
 * @link     www.aoe.com
 */
class EavEntityStore extends AbstractDatabase
{

    /**
     * SQL entity id
     * Prepared on _prepareSqlParams()
     *
     * @var int
     */
    protected $_entityTypeId;

    /**
     * SQL store id
     * Prepared on _prepareSqlParams()
     *
     * @var int
     */
    protected $_storeId;

    /**
     * SQL increment prefix
     * Prepared on _prepareSqlParams()
     *
     * @var int
     */
    protected $_incrementPrefix;

    /**
     * SQL increment last id
     * Prepared on _prepareSqlParams()
     *
     * @var int
     */
    protected $_incrementLastId;

    /**
     * Implementation of the abstract _apply() of the parent class Est_Handler_Magento_AbstractDatabase.
     *
     * @return bool
     * @throws \Exception
     */
    protected function _apply()
    {
        $this->_checkIfTableExists('eav_entity_store');

        $this->_prepareSqlParams();
        $deleteQuery = 'DELETE FROM `' . $this->_tablePrefix . 'eav_entity_store`'
            . ' WHERE `entity_type_id`=:entity_type_id AND `store_id`=:store_id';
        $this->_processDelete(
            $deleteQuery,
            array(
                'entity_type_id' => $this->_entityTypeId,
                'store_id'       => $this->_storeId
            )
        );

        $query = 'INSERT INTO `' . $this->_tablePrefix . 'eav_entity_store`'
            . ' (`entity_type_id`, `store_id`, `increment_prefix`, `increment_last_id`)'
            . ' values(:entity_type_id, :store_id, :increment_prefix, :increment_last_id)';

        $this->_processInsert(
            $query,
            array(
                'entity_type_id'           => $this->_entityTypeId,
                'store_id'                 => $this->_storeId,
                'increment_prefix'         => $this->_incrementPrefix,
                'increment_last_id'        => $this->_incrementLastId
            )
        );

        return true;
    }

    /**
     * Prepares and validates the sql parameters and sets the according class fields
     *
     * @return void
     * @throws \Exception
     */
    protected function _prepareSqlParams()
    {
        $entityTypeId = $this->getParam1();
        if (empty($entityTypeId)) {
            throw new \Exception('Param 1 must be an entity type code or entity type id');
        }

        if (!is_integer($entityTypeId)) {
            $entityTypeId = $this->_getEntityTypeFromCode($entityTypeId);
        }
        $this->_entityTypeId = $entityTypeId;

        $storeId = $this->getParam2();
        if (empty($storeId)) {
            throw new \Exception('Param 2 must contain a store id or store code');
        }
        if (!is_numeric($storeId)) {
            $storeId = $this->_getStoreIdFromCode($storeId);
        }
        $this->_storeId = $storeId;

        $incrementPrefix = $this->getValue();
        if (empty($incrementPrefix)) {
            throw new \Exception('Param 3 must be an increment prefix (integer)');
        }
        if (!is_numeric($incrementPrefix)) {
            throw new \Exception('Param 3 must be an integer');
        }

        $this->_incrementPrefix = $this->_storeId . $incrementPrefix;
        $this->_incrementLastId = $this->_incrementPrefix . '00000000';
    }
}
