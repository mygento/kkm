<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source;

class ApiVersion implements \Magento\Framework\Option\ArrayInterface
{
    public const API_VERSION_4 = 4;

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
            ],
        ];
    }

    /**
     * @return array
     */
    public static function getAllVersions()
    {
        return [self::API_VERSION_4];
    }
}
