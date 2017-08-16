<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Model_Status extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('kkm/status');
    }

    /**Loads object by Invoice or Creditmemo
     * @param $entity
     * @return mixed
     */
    public function loadByEntity($entity)
    {
        $incrementId = $entity->getIncrementId();
        $type        = $entity::HISTORY_ENTITY_NAME;

        $collection = $this->getCollection()
            ->addFieldToFilter('entity_type', $type)
            ->addFieldToFilter('increment_id', $incrementId);

        if ($collection->getSize() > 0) {
            return $collection->getFirstItem();
        }

        $collection = $this->getCollection()
            ->addFieldToFilter('external_id', ['like' => "{$type}_{$incrementId}%"]);

        return $collection->getFirstItem();
    }

    public function getShortStatus()
    {
        if ($this->getData('short_status')) {
            return $this->getData('short_status');
        }

        $response = json_decode($this->getStatus(), true);

        return isset($response['status']) ? $response['status'] : null;
    }

    public function getIncrementId()
    {
        if ($this->getData('increment_id')) {
            return $this->getData('increment_id');
        }

        $parts = explode('_', $this->getExternalId());

        return isset($parts[1]) ? $parts[1] : null;
    }

    public function getEntityType()
    {
        if ($this->getData('entity_type')) {
            return $this->getData('entity_type');
        }

        $eid  = $this->getExternalId();
        $type = substr($eid, 0, strpos($eid, '_'));

        return $type ?: null;
    }
}
