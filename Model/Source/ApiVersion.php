<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source;

class ApiVersion implements \Magento\Framework\Option\ArrayInterface
{
    const API_VERSION_4 = 4;

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::API_VERSION_4,
                'label' => __('Версия 4'),
            ]
        ];
    }

    public static function getAllVersions()
    {
        return [self::API_VERSION_4];
    }
}
