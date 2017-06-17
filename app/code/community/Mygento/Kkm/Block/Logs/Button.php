<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Block_Logs_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $url = $this->getUrl('adminhtml/kkm_cheque/viewlogs');

        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('scalable')
            ->setLabel('View logs')
            ->setDisabled(!Mage::getSingleton('admin/session')->isAllowed('kkm_cheque/getlog'))
            ->setOnClick("setLocation('$url')")
            ->toHtml();

        return $html;
    }
}
