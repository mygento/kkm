<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue\Consumer;

abstract class AbstractConsumer
{
    /**
     * @var \Mygento\Kkm\Helper\TransactionAttempt
     */
    protected $attemptHelper;

    /**
     * @var \Mygento\Kkm\Model\VendorInterface
     */
    protected $vendor;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $publisher;

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    protected $helper;

    /**
     * @var \Mygento\Kkm\Helper\Request
     */
    protected $requestHelper;

    /**
     * @var \Mygento\Kkm\Helper\Error\Proxy
     */
    protected $errorHelper;

    /**
     * @param \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper
     * @param \Mygento\Kkm\Model\VendorInterface $vendor
     * @param \Mygento\Kkm\Helper\Data $helper
     * @param \Mygento\Kkm\Helper\Error\Proxy $errorHelper
     * @param \Mygento\Kkm\Helper\Request $requestHelper
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     */
    public function __construct(
        \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper,
        \Mygento\Kkm\Model\VendorInterface $vendor,
        \Mygento\Kkm\Helper\Data $helper,
        \Mygento\Kkm\Helper\Error\Proxy $errorHelper,
        \Mygento\Kkm\Helper\Request $requestHelper,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher
    ) {
        $this->attemptHelper = $attemptHelper;
        $this->vendor = $vendor;
        $this->publisher = $publisher;
        $this->helper = $helper;
        $this->requestHelper = $requestHelper;
        $this->errorHelper = $errorHelper;
    }

    /**
     * @param \Mygento\Kkm\Api\Queue\MergedRequestInterface $mergedRequest
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    abstract public function sendMergedRequest($mergedRequest);

    /**
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     */
    protected function increaseExternalId($request)
    {
        if (preg_match('/^(.*)__(\d+)$/', $request->getExternalId(), $matches)) {
            $request->setExternalId($matches[1] . '__' . ($matches[2] + 1));
        } else {
            $request->setExternalId($request->getExternalId() . '__1');
        }
    }
}
