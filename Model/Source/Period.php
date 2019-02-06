<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source;

class Period implements \Magento\Framework\Option\ArrayInterface
{
    const WEEKLY_NAME    = 'week';
    const YESTERDAY_NAME = 'yesterday';
    const TODAY_NAME     = 'today';

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