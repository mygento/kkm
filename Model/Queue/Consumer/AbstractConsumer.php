<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue\Consumer;

use Magento\Framework\Exception\InvalidArgumentException;

abstract class AbstractConsumer
{
    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    protected $helper;

    /**
     * @var \Mygento\Kkm\Api\Queue\ConsumerProcessorInterface[]
     */
    protected $consumerProcessors;

    /**
     * @var \Mygento\Kkm\Helper\Request
     */
    protected $requestHelper;

    /**
     * @param \Mygento\Kkm\Helper\Data $helper
     * @param \Mygento\Kkm\Helper\Request $requestHelper
     * @param \Mygento\Kkm\Api\Queue\ConsumerProcessorInterface[] $consumerProcessors
     */
    public function __construct(
        \Mygento\Kkm\Helper\Data $helper,
        \Mygento\Kkm\Helper\Request $requestHelper,
        $consumerProcessors = []
    ) {
        $this->helper = $helper;
        $this->requestHelper = $requestHelper;
        $this->consumerProcessors = $consumerProcessors;
    }

    /**
     * @param \Mygento\Kkm\Api\Queue\MergedRequestInterface $mergedRequest
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    abstract public function sendMergedRequest($mergedRequest);

    /**
     * @param int|string|null $storeId
     * @throws InvalidArgumentException
     * @return \Mygento\Kkm\Api\Queue\ConsumerProcessorInterface
     */
    protected function getConsumerProcessor($storeId = null)
    {
        $currentVendorCode = $this->helper->getCurrentVendorCode($storeId);

        if (!isset($this->consumerProcessors[$currentVendorCode])) {
            throw new InvalidArgumentException(__('No such Kkm vendor: %1', $currentVendorCode));
        }

        return $this->consumerProcessors[$currentVendorCode];
    }
}
