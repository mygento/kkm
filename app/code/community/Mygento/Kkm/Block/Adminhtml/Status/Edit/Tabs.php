<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2018 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Block_Adminhtml_Status_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('status_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('kkm')->__('Status Information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label' => Mage::helper('kkm')->__('Status Information'),
            'title' => Mage::helper('kkm')->__('Status Information'),
            'content' => $this->getLayout()->createBlock('kkm/adminhtml_status_edit_tab_form')->toHtml(),
        ));
        return parent::_beforeToHtml();
    }
}
