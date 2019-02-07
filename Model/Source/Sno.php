<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source;

class Sno implements \Magento\Framework\Option\ArrayInterface
{
    const RECEIPT_SNO_OSN                = 'osn';
    const RECEIPT_SNO_USN_INCOME         = 'usn_income';
    const RECEIPT_SNO_USN_INCOME_OUTCOME = 'usn_income_outcome';
    const RECEIPT_SNO_ENVD               = 'envd';
    const RECEIPT_SNO_ESN                = 'esn';
    const RECEIPT_SNO_PATENT             = 'patent';

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::RECEIPT_SNO_OSN,
                'label' => __('общая СН'),
            ],
            [
                'value' => self::RECEIPT_SNO_USN_INCOME,
                'label' => __('упрощенная СН (доходы)'),
            ],
            [
                'value' => self::RECEIPT_SNO_USN_INCOME_OUTCOME,
                'label' => __('упрощенная СН (доходы минус расходы)'),
            ],
            [
                'value' => self::RECEIPT_SNO_ENVD,
                'label' => __('единый налог на вмененный доход'),
            ],
            [
                'value' => self::RECEIPT_SNO_ESN,
                'label' => __('единый сельскохозяйственный налог'),
            ],
            [
                'value' => self::RECEIPT_SNO_PATENT,
                'label' => __('патентная СН'),
            ],
        ];
    }
}
