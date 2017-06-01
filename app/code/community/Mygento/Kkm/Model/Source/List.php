<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2017 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Model_Source_List
{

    public function getAllOptions()
    {
        $attributes = Mage::getModel('eav/config')->getEntityType(Mage_Catalog_Model_Product::ENTITY)->getAttributeCollection();

        $attributes->addFieldToFilter('main_table.frontend_input', array('neq' => 'hidden'));
        $attributes->addFieldToFilter('main_table.frontend_input', array('neq' => 'multiselect'));
        $attributes->addFieldToFilter('main_table.frontend_input', array('neq' => 'boolean'));
        $attributes->addFieldToFilter('main_table.frontend_input', array('neq' => 'date'));
        $attributes->addFieldToFilter('main_table.frontend_input', array('neq' => 'image'));
        $attributes->addFieldToFilter('main_table.frontend_input', array('neq' => 'price'));
        $attributes->addFieldToFilter('used_in_product_listing', '1');

        $attributes->setOrder('frontend_label', 'ASC');

        $_options = array();

        $_options[] = array(
            'label' => Mage::helper('kkm')->__('No usage'),
            'value' => 0
        );

        foreach ($attributes as $attr) {
            $label = $attr->getStoreLabel() ? $attr->getStoreLabel() : $attr->getFrontendLabel();
            if ('' != $label) {
                $_options[] = array('label' => $label, 'value' => $attr->getAttributeCode());
            }
        }
        return $_options;
    }

    public function toOptionArray()
    {
        return $this->getAllOptions();
    }
}
