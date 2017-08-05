<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Model_Resource_Status extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('kkm/status', 'id');
    }

    protected function _prepareDataForSave(Mage_Core_Model_Abstract $object)
    {
        $currentTime = date(Varien_Date::DATETIME_PHP_FORMAT, Mage::getModel('core/date')->timestamp(time()));
        if ((!$object->getId() || $object->isObjectNew()) && !$object->getCreatedAt()) {
            $object->setCreatedAt($currentTime);
        }
        $object->setUpdatedAt($currentTime);
        $data = parent::_prepareDataForSave($object);

        return $data;
    }
}
