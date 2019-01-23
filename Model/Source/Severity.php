<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Kkm
 */
namespace Mygento\Kkm\Model\Source;

/**
 * Class Severity
 */
class Severity implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => \Monolog\Logger::CRITICAL,
                'label' => ('CRITICAL')
            ],
            [
                'value' => \Monolog\Logger::ERROR,
                'label' => ('ERROR')
            ],
            [
                'value' => \Monolog\Logger::WARNING,
                'label' => ('WARN')
            ],
            [
                'value' => \Monolog\Logger::DEBUG,
                'label' => ('DEBUG')
            ],
        ];
    }
}
