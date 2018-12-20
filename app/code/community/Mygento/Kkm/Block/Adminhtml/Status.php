<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2018 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Block_Adminhtml_Status extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        $this->_controller = 'adminhtml_status';
        $this->_blockGroup = 'kkm';
        $this->_headerText = Mage::helper('kkm')->__('KKM Statuses');

        parent::__construct();
    }
}
