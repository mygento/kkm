<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source;

use Mygento\Kkm\Api\Data\ResponseInterface;

class TransactionStatus implements \Magento\Framework\Data\OptionSourceInterface
{
    const OPTIONS = [
        ResponseInterface::STATUS_WAIT,
        ResponseInterface::STATUS_DONE,
        ResponseInterface::STATUS_FAIL,
    ];

    /**
     * Return option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach (self::OPTIONS as $option) {
            $options[] = [
                'value' => $option,
                'label' => __($option),
            ];
        }

        return $options;
    }
}
