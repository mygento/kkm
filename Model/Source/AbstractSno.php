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
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => $this->osnValue,
                'label' => __('общая СН'),
            ],
            [
                'value' => $this->usnIncomeValue,
                'label' => __('упрощенная СН (доходы)'),
            ],
            [
                'value' => $this->usnIncomeOutcomeValue,
                'label' => __('упрощенная СН (доходы минус расходы)'),
            ],
            [
                'value' => $this->envdValue,
                'label' => __('единый налог на вмененный доход'),
            ],
            [
                'value' => $this->esnValue,
                'label' => __('единый сельскохозяйственный налог'),
            ],
            [
                'value' => $this->patentValue,
                'label' => __('патентная СН'),
            ],
        ];
    }
}
