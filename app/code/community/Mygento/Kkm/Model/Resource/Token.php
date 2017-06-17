<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */

/**
 * Class Mygento_Kkm_Model_Resource_Token
 * @deprecated after 1.0.3
 */
class Mygento_Kkm_Model_Resource_Token extends Mage_Core_Model_Resource_Db_Abstract
{

    protected function _construct()
    {
        $this->_init('kkm/token', 'id');
    }
}
