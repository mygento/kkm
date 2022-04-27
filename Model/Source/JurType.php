<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source;

use Magento\Framework\Data\OptionSourceInterface as OptionSourceInterfaceAlias;

class JurType implements OptionSourceInterfaceAlias
{
    public const ENTREPRENEUR = 'entrepreneur';
    public const ORGANIZATION = 'organization';
    public const OPTIONS = [
        self::ENTREPRENEUR => 'Individual Entrepreneur',
        self::ORGANIZATION => 'Organization',
    ];

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $optionArray = array_map(
            function ($value, $label) {
                return [
                    'value' => $value,
                    'label' => __($label),
                ];
            },
            array_keys(self::OPTIONS),
            self::OPTIONS
        );

        array_unshift($optionArray, [
            'label' => __('No usage'),
            'value' => 0,
        ]);

        return $optionArray;
    }
}
