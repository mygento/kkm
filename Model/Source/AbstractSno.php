<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

abstract class AbstractSno implements OptionSourceInterface
{
    protected const RECEIPT_SNO_OSN = '';
    protected const RECEIPT_SNO_USN_INCOME = '';
    protected const RECEIPT_SNO_USN_INCOME_OUTCOME = '';
    protected const RECEIPT_SNO_ENVD = '';
    protected const RECEIPT_SNO_ESN = '';
    protected const RECEIPT_SNO_PATENT = '';

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => static::RECEIPT_SNO_OSN,
                'label' => __('общая СН'),
            ],
            [
                'value' => static::RECEIPT_SNO_USN_INCOME,
                'label' => __('упрощенная СН (доходы)'),
            ],
            [
                'value' => static::RECEIPT_SNO_USN_INCOME_OUTCOME,
                'label' => __('упрощенная СН (доходы минус расходы)'),
            ],
            [
                'value' => static::RECEIPT_SNO_ENVD,
                'label' => __('единый налог на вмененный доход'),
            ],
            [
                'value' => static::RECEIPT_SNO_ESN,
                'label' => __('единый сельскохозяйственный налог'),
            ],
            [
                'value' => static::RECEIPT_SNO_PATENT,
                'label' => __('патентная СН'),
            ],
        ];
    }
}
