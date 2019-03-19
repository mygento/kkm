<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue;

use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Exception\VendorBadServerAnswerException;
use Mygento\Kkm\Model\Processor;

class Consumer
{
    /**
     * @var \Mygento\Kkm\Model\VendorInterface
     */
    private $vendor;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    private $publisher;

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $helper;

    /**
     * @var \Mygento\Kkm\Helper\Request
     */
    private $requestHelper;

    /**
     * @var \Mygento\Kkm\Helper\Error\Proxy
     */
    private $errorHelper;

    /**
     * Consumer constructor.
     * @param \Mygento\Kkm\Model\VendorInterface $vendor
     * @param \Mygento\Kkm\Helper\Data $helper
     * @param \Mygento\Kkm\Helper\Error\Proxy $errorHelper
     * @param \Mygento\Kkm\Helper\Request $requestHelper
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     */
    public function __construct(
        \Mygento\Kkm\Model\VendorInterface $vendor,
        \Mygento\Kkm\Helper\Data $helper,
        \Mygento\Kkm\Helper\Error\Proxy $errorHelper,
        \Mygento\Kkm\Helper\Request $requestHelper,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher
    ) {
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
    public function sendSellMergedRequest($mergedRequest)
    {
        $requests = $mergedRequest->getRequests();
        $this->helper->debug(count($requests) . ' SellRequests received to process.');
        foreach ($requests as $request) {
            $this->sendSellRequest($request);
        }
    }

    /**
     * @param \Mygento\Kkm\Api\Queue\MergedRequestInterface $mergedRequest
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendRefundMergedRequest($mergedRequest)
    {
        $requests = $mergedRequest->getRequests();
        $this->helper->debug(count($requests) . ' RefundRequests received to process.');

        foreach ($requests as $request) {
            $this->sendRefundRequest($request);
        }
    }

    /**
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendSellRequest($request)
    {
        $this->updateRetries($request);

        try {
            $this->vendor->sendSellRequest($request);
        } catch (VendorBadServerAnswerException $e) {
            $this->helper->critical($e->getMessage());

            $this->publisher->publish(Processor::TOPIC_NAME_SELL, $request);
        } catch (\Exception $e) {
            $entity = $this->requestHelper->getEntityByRequest($request);
            $this->errorHelper->processKkmChequeRegistrationError($entity, $e);
        }
    }

    /**
     * @param RequestInterface $request
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendRefundRequest($request)
    {
        $this->updateRetries($request);

        try {
            $this->vendor->sendRefundRequest($request);
        } catch (VendorBadServerAnswerException $e) {
            $this->helper->critical($e->getMessage());

            $this->publisher->publish(Processor::TOPIC_NAME_REFUND, $request);
        } catch (\Exception $e) {
            $entity = $this->requestHelper->getEntityByRequest($request);
            $this->errorHelper->processKkmChequeRegistrationError($entity, $e);
        }
    }

    /**
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     * @return \Mygento\Kkm\Api\Data\RequestInterface
     */
    private function updateRetries($request)
    {
        if ($request->getRetryCount() === null) {
            $request->setRetryCount(0);

            return $request;
        }
        $request->setRetryCount(
            $request->getRetryCount() + 1
        );

        return $request;
    }
}
