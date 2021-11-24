<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Mygento\Kkm\Api\Data\TransactionAttemptInterface;

class AttemptStatus implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => TransactionAttemptInterface::STATUS_NEW,
                'label' => __('New'),
            ],
            [
                'value' => TransactionAttemptInterface::STATUS_SENT,
                'label' => __('Sent'),
            ],
            [
                'value' => TransactionAttemptInterface::STATUS_ERROR,
                'label' => __('Error'),
            ],
            [
                'value' => TransactionAttemptInterface::STATUS_DONE,
                'label' => __('Done'),
            ],
        ];
    }
}
