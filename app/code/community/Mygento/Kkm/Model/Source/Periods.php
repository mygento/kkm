<?php

/**
 *
 *
 * @category Mygento
 * @package Mygento_Kkm
 * @copyright 2019 NKS LLC. (https://www.mygento.ru)
 */
class Mygento_Kkm_Model_Source_Periods
{

    const WEEKLY_PERIOD = 'week';
    const DAILY_PREV_PERIOD = 'dailyprev';
    const DAILY_PERIOD = 'daily';

    /**
     * Options source
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::WEEKLY_PERIOD,
                'label' => Mage::helper('kkm')->__('Weekly (This week)'),
            ],
            [
                'value' => self::DAILY_PREV_PERIOD,
                'label' => Mage::helper('kkm')->__('Daily (Yesterday)'),
            ],
            [
                'value' => self::DAILY_PERIOD,
                'label' => Mage::helper('kkm')->__('Daily (Today)'),
            ],
        ];
    }
}
