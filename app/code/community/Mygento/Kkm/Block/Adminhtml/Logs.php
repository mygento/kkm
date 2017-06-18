<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Block_Adminhtml_Logs extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        $this->_controller = 'adminhtml_logs';
        $this->_blockGroup = 'kkm';
        $this->_headerText = Mage::helper('kkm')->__('Logs Viewer');

        $url = Mage::getModel('adminhtml/url')->getUrl('adminhtml/kkm_cheque/clearlogs');
        $this->addButton('clear', array(
            'label' => Mage::helper('kkm')->__('Clear logs'),
            'onclick'   => 'setLocation(\'' . $url .'\')',
        ));

        parent::__construct();
        $this->removeButton('add');
    }
}
