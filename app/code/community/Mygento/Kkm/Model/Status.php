<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright Copyright © 2017 NKS LLC. (http://www.mygento.ru)
 */
class Mygento_Kkm_Model_Status extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('kkm/status');
    }

}
