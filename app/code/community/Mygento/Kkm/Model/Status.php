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

//    public function loadByInvoice($incrementId)
//    {
//        return $this->loadEntity($incrementId, 'invoice');
//    }
//
//    public function loadByCreditmemo($incrementId)
//    {
//        return $this->loadEntity($incrementId, 'creditmemo');
//    }
//
//    public function loadEntity($incrementId, $type)
//    {
//        $collection = $this->getCollection()
//            ->addFieldToFilter('entity_type', $type)
//            ->addFieldToFilter('increment_id', $incrementId);
//
//        if ($collection->getSize() > 0) {
//            return $collection->getFirstItem();
//        }
//
//        $collection = $this->getCollection()
//            ->addFieldToFilter('external_id', ['like' => "{$type}_{$incrementId}%"]);
//
//        return $collection->getFirstItem();
//    }

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
}
