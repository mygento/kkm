<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue;

use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Exception\VendorBadServerAnswerException;
use Mygento\Kkm\Exception\VendorNonFatalErrorException;
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
        try {
            $this->vendor->sendSellRequest($request);
        } catch (VendorNonFatalErrorException $e) {
            $this->helper->info($e->getMessage());

            $request->setIgnoreTrialsNum(false);
            $this->increaseExternalId($request);
            $this->publisher->publish(Processor::TOPIC_NAME_SELL, $request);
        } catch (VendorBadServerAnswerException $e) {
            $this->helper->info($e->getMessage());

            $request->setIgnoreTrialsNum(false);
            $this->publisher->publish(Processor::TOPIC_NAME_SELL, $request);
        } catch (\Throwable $e) {
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
        try {
            $this->vendor->sendRefundRequest($request);
        } catch (VendorBadServerAnswerException $e) {
            $this->helper->critical($e->getMessage());

            $request->setIgnoreTrialsNum(false);
            $this->publisher->publish(Processor::TOPIC_NAME_REFUND, $request);
        } catch (\Throwable $e) {
            $entity = $this->requestHelper->getEntityByRequest($request);
            $this->errorHelper->processKkmChequeRegistrationError($entity, $e);
        }
    }

    /**
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     */
    private function increaseExternalId($request)
    {
        if (preg_match('/^(.*)__(\d+)$/', $request->getExternalId(), $matches)) {
            $request->setExternalId($matches[1] . '__' . ($matches[2] + 1));
        } else {
            $request->setExternalId($request->getExternalId() . '__1');
        }
    }
}
