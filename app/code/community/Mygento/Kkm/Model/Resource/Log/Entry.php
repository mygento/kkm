<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (http://www.mygento.ru)
 */
class Mygento_Kkm_Model_Resource_Log_Entry extends Mage_Core_Model_Resource_Db_Abstract
{

    protected function _construct()
    {
        $this->_init('kkm/log_entry', 'entity_id');
    }

}
