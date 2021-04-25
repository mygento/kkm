<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source;

class Sno implements \Magento\Framework\Option\ArrayInterface
{
    const RECEIPT_SNO_OSN = 'osn';
    const RECEIPT_SNO_USN_INCOME = 'usn_income';
    const RECEIPT_SNO_USN_INCOME_OUTCOME = 'usn_income_outcome';
    const RECEIPT_SNO_ENVD = 'envd';
    const RECEIPT_SNO_ESN = 'esn';
    const RECEIPT_SNO_PATENT = 'patent';

    // phpcs:disable
    protected $osnValue = self::RECEIPT_SNO_OSN;

    protected $usnIncomeValue = self::RECEIPT_SNO_USN_INCOME;

    protected $usnIncomeOutcomeValue = self::RECEIPT_SNO_USN_INCOME_OUTCOME;

    protected $envdValue = self::RECEIPT_SNO_ENVD;

    protected $esnValue = self::RECEIPT_SNO_ESN;

    protected $patentValue = self::RECEIPT_SNO_PATENT;

    // phpcs:enable

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
