<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source;

class Period implements \Magento\Framework\Data\OptionSourceInterface
{
    public const WEEKLY_NAME = 'week';
    public const YESTERDAY_NAME = 'yesterday';
    public const TODAY_NAME = 'today';

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::WEEKLY_NAME,
                'label' => __('Weekly (This week)'),
            ],
            [
                'value' => self::YESTERDAY_NAME,
                'label' => __('Daily (Yesterday)'),
            ],
            [
                'value' => self::TODAY_NAME,
                'label' => __('Daily (Today)'),
            ],
        ];
    }
}
