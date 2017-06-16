<?php
/**
 * @author Mygento Team
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Kkm
 */
namespace Mygento\Kkm\Model\Source;

/**
 * Class Vendor
 */
class Vendor implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '0',
                'label' => __('-- Please select --')
            ],
            [
                'value' => 'atol',
                'label' => __('Atol')
            ]
        ];
    }

}
