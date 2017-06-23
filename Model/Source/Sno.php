<?php
/**
 * @author Mygento Team
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Kkm
 */
namespace Mygento\Kkm\Model\Source;

/**
 * Class Sno
 */
class Sno implements \Magento\Framework\Option\ArrayInterface
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
                'value' => "osn",
                'label' => __('общая СН')
            ],
            [
                'value' => "usn_income",
                'label' => __('упрощенная СН (доходы)')
            ],
            [
                'value' => "usn_income_outcome",
                'label' => __('упрощенная СН (доходы минус расходы)')
            ],
            [
                'value' => "envd",
                'label' => __('единый налог на вмененный доход')
            ],
            [
                'value' => "esn",
                'label' => __('единый сельскохозяйственный налог')
            ],
            [
                'value' => "patent",
                'label' => __('патентная СН')
            ]
        ];
    }
}
