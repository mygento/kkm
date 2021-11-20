<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source;

use Mygento\Kkm\Api\Data\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;

class OperationType implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => RequestInterface::SELL_OPERATION_TYPE,
                'label' => __('Sell'),
            ],
            [
                'value' => RequestInterface::REFUND_OPERATION_TYPE,
                'label' => __('Refund'),
            ],
            [
                'value' => RequestInterface::RESELL_SELL_OPERATION_TYPE,
                'label' => __('Resell Sell'),
            ],
            [
                'value' => RequestInterface::RESELL_REFUND_OPERATION_TYPE,
                'label' => __('Resell Refund'),
            ]
        ];
    }
}
