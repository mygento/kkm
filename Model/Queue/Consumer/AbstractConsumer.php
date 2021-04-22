<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue\Consumer;

abstract class AbstractConsumer
{
    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    protected $helper;

    /**
     * @var \Mygento\Kkm\Model\Queue\Consumer\ConsumerProcessorFactory
     */
    protected $consumerProcessorFactory;

    /**
     * @var \Mygento\Kkm\Helper\Request
     */
    protected $requestHelper;

    /**
     * Consumer constructor.
     * @param \Mygento\Kkm\Helper\Data $helper
     * @param \Mygento\Kkm\Model\Queue\Consumer\ConsumerProcessorFactory $consumerProcessorFactory
     * @param \Mygento\Kkm\Helper\Request $requestHelper
     */
    public function __construct(
        \Mygento\Kkm\Helper\Data $helper,
        \Mygento\Kkm\Model\Queue\Consumer\ConsumerProcessorFactory $consumerProcessorFactory,
        \Mygento\Kkm\Helper\Request $requestHelper
    ) {
        $this->helper = $helper;
        $this->consumerProcessorFactory = $consumerProcessorFactory;
        $this->requestHelper = $requestHelper;
    }

    /**
     * @param \Mygento\Kkm\Api\Queue\MergedRequestInterface $mergedRequest
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    abstract public function sendMergedRequest($mergedRequest);

    /**
     * @param string|int|null $storeId
     * @return \Mygento\Kkm\Api\Queue\ConsumerProcessorInterface
     * @throws \Magento\Framework\Exception\InvalidArgumentException
     */
    protected function getConsumerProcessor($storeId = null)
    {
        return $this->consumerProcessorFactory->create($this->helper->getCurrentVendorCode($storeId));
    }


}
