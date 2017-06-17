<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */

/**
 * Class Mygento_Kkm_Model_Token
 * @deprecated after 1.0.3
 */
class Mygento_Kkm_Model_Token extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('kkm/token');
    }
}
