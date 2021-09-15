<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source\Atol;

use Magento\Framework\Data\OptionSourceInterface;

class ErrorType implements OptionSourceInterface
{
    public const UNKNOWN = 'unknown';
    public const BAD_SERVER_ANSWER = 'bad_answer';
    public const SYSTEM = 'system';
    public const TIMEOUT = 'timeout';
    public const AGENT = 'agent';
    public const DRIVER = 'driver';

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::UNKNOWN,
                'label' => __('Unknown'),
            ],
            [
                'value' => self::BAD_SERVER_ANSWER,
                'label' => __('Bad Server Answer'),
            ],
            [
                'value' => self::SYSTEM,
                'label' => __('System'),
            ],
            [
                'value' => self::TIMEOUT,
                'label' => __('Timeout'),
            ],
            [
                'value' => self::AGENT,
                'label' => __('Agent'),
            ],
            [
                'value' => self::DRIVER,
                'label' => __('Driver'),
            ],
        ];
    }
}
