<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source\Transaction\Grid;

use Magento\Framework\Data\OptionSourceInterface;
use Mygento\Kkm\Model\Atol\Response;

class KkmStatus implements OptionSourceInterface
{
    public const OPTIONS = [
        Response::STATUS_WAIT,
        Response::STATUS_DONE,
        Response::STATUS_FAIL,
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
