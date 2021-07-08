<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue\Consumer;

use Magento\Framework\Exception\InvalidArgumentException;

class ConsumerProcessorFactory
{
    /**
     * @var array
     */
    private $processorFactories;

    /**
     * @param array $processorFactories
     */
    public function __construct(
        $processorFactories = []
    ) {
        $this->processorFactories = $processorFactories;
    }

    /**
     * @param string $vendorCode
     * @throws InvalidArgumentException
     * @return \Mygento\Kkm\Api\Queue\ConsumerProcessorInterface
     */
    public function create($vendorCode)
    {
        if (!isset($this->processorFactories[$vendorCode])) {
            throw new InvalidArgumentException(__('No such Kkm vendor: %1', $vendorCode));
        }

        return $this->processorFactories[$vendorCode]->create();
    }
}
