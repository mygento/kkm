<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source;


class ApiVersion implements \Magento\Framework\Option\ArrayInterface
{
    const API_VERSION_4                = 4;

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
