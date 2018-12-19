<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2018 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Block_Adminhtml_Status_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_status';
        $this->_blockGroup = 'kkm';
        $this->_addButton('saveandcontinue', array(
            'label' => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick' => 'saveAndContinueEdit()',
            'class' => 'save',
            ), -100);
        $this->_formScripts[] = "
			function saveAndContinueEdit(){
				editForm.submit($('edit_form').action+'back/edit/');
			}
		";
        $this->_removeButton('delete');
    }

    public function getHeaderText()
    {
        if (Mage::registry('kkm_status_data') && Mage::registry('kkm_status_data')->getId()) {
            return Mage::helper('kkm')->__("Edit Status %s", $this->htmlEscape(Mage::registry('kkm_status_data')->getId()));
        }

        return Mage::helper('kkm')->__('Add Status');
    }
}
