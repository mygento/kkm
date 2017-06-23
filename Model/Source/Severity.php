<?php
/**
 * @author Mygento Team
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
                'value' => \Zend\Log\Logger::CRIT,
                'label' => ('CRITICAL')
            ],
            [
                'value' => \Zend\Log\Logger::ERR,
                'label' => ('ERROR')
            ],
            [
                'value' => \Zend\Log\Logger::WARN,
                'label' => ('WARN')
            ],
            [
                'value' => \Zend\Log\Logger::DEBUG,
                'label' => ('DEBUG')
            ],
        ];
    }
}
