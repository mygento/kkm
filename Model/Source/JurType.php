<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source;

use Magento\Framework\Data\OptionSourceInterface as OptionSourceInterfaceAlias;

class JurType implements OptionSourceInterfaceAlias
{
    const ENTREPRENEUR = 'entrepreneur';
    const ORGANIZATION = 'organization';

    const OPTIONS = [
        self::ENTREPRENEUR => 'Individual Entrepreneur',
        self::ORGANIZATION => 'Organization',
    ];

    public function toOptionArray()
    {
        return array_map(
            function ($value, $label) {
                return [
                    'value' => $value,
                    'label' => __($label),
                ];
            },
            array_keys(self::OPTIONS),
            self::OPTIONS
        );
    }
}
