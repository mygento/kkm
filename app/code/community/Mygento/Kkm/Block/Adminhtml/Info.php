<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Block_Adminhtml_Info extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
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
        $helper = Mage::helper('kkm');
        $html   = '<tr><td class="label">' . $helper->__('Discount Helper Version:') . '</td>'
            . '<td class="value" style="font-weight: bold;">' .
            Mygento_Kkm_Helper_Discount::VERSION . '</td></tr>';

        return $html;
    }
}
