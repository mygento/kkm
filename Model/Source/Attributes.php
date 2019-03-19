<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;

/**
 * Class Attributes
 */
class Attributes implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Possible product attributes
     *
     * @SuppressWarnings(PHPMD)
     * @return array
     */
    public function getAllOptions()
    {
        $obMan = \Magento\Framework\App\ObjectManager::getInstance();

        $coll = $obMan->create(Collection::class);

        $coll->addFieldToFilter(\Magento\Eav\Model\Entity\Attribute\Set::KEY_ENTITY_TYPE_ID, 4);

        $coll->addFieldToFilter('main_table.frontend_input', ['neq' => 'hidden']);
        $coll->addFieldToFilter('main_table.frontend_input', ['neq' => 'multiselect']);
        $coll->addFieldToFilter('main_table.frontend_input', ['neq' => 'boolean']);
        $coll->addFieldToFilter('main_table.frontend_input', ['neq' => 'date']);
        $coll->addFieldToFilter('main_table.frontend_input', ['neq' => 'image']);
        $coll->addFieldToFilter('main_table.frontend_input', ['neq' => 'price']);
        $coll->addFieldToFilter('used_in_product_listing', '1');
        $coll->setOrder('frontend_label', 'ASC');

        $attrAll = $coll->load()->getItems();

        $options = [];

        $options[] = [
            'label' => __('No usage'),
            'value' => 0,
        ];

        // Loop over all attributes
        foreach ($attrAll as $attr) {
            $label = $attr->getStoreLabel() ? $attr->getStoreLabel() : $attr->getFrontendLabel();
            if ('' != $label) {
                $options[] = ['label' => $label, 'value' => $attr->getAttributeCode()];
            }
        }

        return $options;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->getAllOptions();
    }
}
