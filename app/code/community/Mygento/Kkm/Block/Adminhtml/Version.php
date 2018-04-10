<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2018 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Block_Adminhtml_Version extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     *
     * @SuppressWarnings("unused")
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $version = (string)Mage::getConfig()->getNode()->modules->Mygento_Kkm->version;

        $helper = Mage::helper('kkm');
        $html   = '<tr><td class="label">' . $helper->__('Module Version:') . '</td>'
            . '<td class="value" style="font-weight: bold;">' . $version . '</td></tr>';

        return $html;
    }
}
