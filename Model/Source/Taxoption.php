<?php
/**
 * @author Mygento Team
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Kkm
 */
namespace Mygento\Kkm\Model\Source;

/**
 * Class Taxoption
 */
class Taxoption implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'none',
                'label' => __('Without VAT')
            ],
            [
                'value' => 'vat0',
                'label' => __('vat0')
            ],
            [
                'value' => 'vat10',
                'label' => __('vat10')
            ],
            [
                'value' => 'vat18',
                'label' => __('vat18')
            ],
            [
                'value' => 'vat110',
                'label' => __('vat110')
            ],
            [
                'value' => 'vat118',
                'label' => __('vat118')
            ]
        ];
    }
}
