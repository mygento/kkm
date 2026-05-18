<?php

/**
 * @author Mygento Team
 * @copyright 2017-2026 Mygento (https://www.mygento.com)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class ApiVersion implements ArrayInterface
{
    public const API_VERSION_4 = 4;
    public const API_VERSION_5 = 5;

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
                'label' => __('Version %1', 4),
            ],
            [
                'value' => self::API_VERSION_5,
                'label' => __('Version %1', 5),
            ],
        ];
    }

    /**
     * @return array
     */
    public static function getAllVersions()
    {
        return [
            self::API_VERSION_4,
            self::API_VERSION_5,
        ];
    }
}
