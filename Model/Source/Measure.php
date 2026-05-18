<?php

/**
 * @author Mygento Team
 * @copyright 2017-2026 Mygento (https://www.mygento.com)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Measure implements OptionSourceInterface
{
    public const MEASURE_UNITS = 0;
    public const MEASURE_GRAM = 10;
    public const MEASURE_KILOGRAM = 11;
    public const MEASURE_TONS = 12;
    public const MEASURE_CENTIMETER = 20;
    public const MEASURE_DECIMETER = 21;
    public const MEASURE_METER = 22;
    public const MEASURE_SQUARE_CENTIMETER = 30;
    public const MEASURE_SQUARE_DECIMETER = 31;
    public const MEASURE_SQUARE_METER = 32;
    public const MEASURE_MILLILITER = 40;
    public const MEASURE_LITER = 41;
    public const MEASURE_CUBIC_METER = 42;
    public const MEASURE_KILOWATT_HOUR = 50;
    public const MEASURE_GIGACALORIE = 51;
    public const MEASURE_DAY = 70;
    public const MEASURE_HOUR = 71;
    public const MEASURE_MINUTE = 72;
    public const MEASURE_SECOND = 73;
    public const MEASURE_KILOBYTE = 80;
    public const MEASURE_MEGABYTE = 81;
    public const MEASURE_GIGABYTE = 82;
    public const MEASURE_TERABYTE = 83;
    public const MEASURE_OTHER = 255;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $result = [];
        foreach ($this->toHashArray() as $value => $label) {
            $result[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $result;
    }

    /**
     * @return array
     */
    public function toHashArray()
    {
        return [
            self::MEASURE_UNITS => __('Applies to items of calculation that can be sold individually or in units'),
            self::MEASURE_GRAM => __('Gram'),
            self::MEASURE_KILOGRAM => __('Kilogram'),
            self::MEASURE_TONS => __('Tons'),
            self::MEASURE_CENTIMETER => __('Centimeter'),
            self::MEASURE_DECIMETER => __('Decimeter'),
            self::MEASURE_METER => __('Meter'),
            self::MEASURE_SQUARE_CENTIMETER => __('Square centimeter'),
            self::MEASURE_SQUARE_DECIMETER => __('Square decimeter'),
            self::MEASURE_SQUARE_METER => __('Square meter'),
            self::MEASURE_MILLILITER => __('Milliliter'),
            self::MEASURE_LITER => __('Liter'),
            self::MEASURE_CUBIC_METER => __('Cubic meter'),
            self::MEASURE_KILOWATT_HOUR => __('Kilowatt hour'),
            self::MEASURE_GIGACALORIE => __('Gigacalorie'),
            self::MEASURE_DAY => __('Day (twenty-four hours)'),
            self::MEASURE_HOUR => __('Hour'),
            self::MEASURE_MINUTE => __('Minute'),
            self::MEASURE_SECOND => __('Second'),
            self::MEASURE_KILOBYTE => __('Kilobyte'),
            self::MEASURE_MEGABYTE => __('Megabyte'),
            self::MEASURE_GIGABYTE => __('Gigabyte'),
            self::MEASURE_TERABYTE => __('Terabyte'),
            self::MEASURE_OTHER => __('Applies when using other units of measurement'),
        ];
    }
}
