<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source;

use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Framework\Data\OptionSourceInterface;

class CustomerAttributes implements OptionSourceInterface
{
    /**
     * @var CustomerResource
     */
    private $customerResource;

    /**
     * @param CustomerResource $customerResource
     */
    public function __construct(
        CustomerResource $customerResource
    ) {
        $this->customerResource = $customerResource;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $customerAttributes = $this->customerResource->loadAllAttributes()->getAttributesByCode();

        $attributes = [];
        /** @var Attribute $attribute */
        foreach ($customerAttributes as $attribute) {
            $label = $attribute->getFrontendLabel();
            if (!$label) {
                continue;
            }
            // skip "binary" attributes
            if (in_array($attribute->getFrontendInput(), ['file', 'image'])) {
                continue;
            }

            $attributes[$attribute->getAttributeCode()] = $label;
        }
        asort($attributes);

        $optionArray = array_map(
            function ($attributeCode, $label) {
                return [
                    'value' => $attributeCode,
                    'label' => $label,
                ];
            },
            array_keys($attributes),
            $attributes
        );

        array_unshift($optionArray, [
            'label' => __('No usage'),
            'value' => 0,
        ]);

        return $optionArray;
    }
}
