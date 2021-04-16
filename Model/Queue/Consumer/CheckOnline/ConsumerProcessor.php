<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue\Consumer\CheckOnline;

use Magento\Framework\Exception\InputException;
use Mygento\Kkm\Api\Processor\SendInterface;
use Mygento\Kkm\Api\Processor\UpdateInterface;
use Mygento\Kkm\Api\Queue\ConsumerProcessorInterface;
use Mygento\Kkm\Exception\VendorBadServerAnswerException;
use Mygento\Kkm\Exception\VendorNonFatalErrorException;

class ConsumerProcessor implements ConsumerProcessorInterface
{
    /**
     * @var \Mygento\Kkm\Model\CheckOnline\Vendor
     */
    private $vendor;

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    private $publisher;

    /**
     * @var \Mygento\Kkm\Helper\Request
     */
    private $requestHelper;

    /**
     * @var \Mygento\Kkm\Helper\Error\Proxy
     */
    private $errorHelper;

    /**
     * @var \Mygento\Kkm\Helper\TransactionAttempt
     */
    private $attemptHelper;

    /**
     * ConsumerProcessor constructor.
     * @param \Mygento\Kkm\Model\CheckOnline\Vendor $vendor
     * @param \Mygento\Kkm\Helper\Data $helper
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     * @param \Mygento\Kkm\Helper\Request $requestHelper
     * @param \Mygento\Kkm\Helper\Error\Proxy $errorHelper
     * @param \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper
     */
    public function __construct(
        \Mygento\Kkm\Model\CheckOnline\Vendor $vendor,
        \Mygento\Kkm\Helper\Data $helper,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Mygento\Kkm\Helper\Request $requestHelper,
        \Mygento\Kkm\Helper\Error\Proxy $errorHelper,
        \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper
    ) {
        $this->vendor = $vendor;
        $this->helper = $helper;
        $this->publisher = $publisher;
        $this->requestHelper = $requestHelper;
        $this->errorHelper = $errorHelper;
        $this->attemptHelper = $attemptHelper;
    }

    /**
     * @inheritDoc
     */
    public function processSell($request)
    {
        $this->processSellAndRefund($request, SendInterface::TOPIC_NAME_SELL);
    }

    /**
     * @inheritDoc
     */
    public function processRefund($request)
    {
        $this->processSellAndRefund($request, SendInterface::TOPIC_NAME_REFUND);
    }

    /**
     * @inheritDoc
     */
    public function processResell($request)
    {
        // todo implement
    }

    /**
     * @inheritDoc
     */
    public function processUpdate($request)
    {
        return;
    }

    /**
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     * @param string $topicName
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     */
    private function processSellAndRefund($request, $topicName)
    {
        try {
            $this->vendor->sendSellRequest($request);
        } catch (VendorNonFatalErrorException|VendorBadServerAnswerException $e) {
            $this->helper->info($e->getMessage());

            if ($this->helper->isUseCustomRetryIntervals($request->getEntityStoreId())) {
                if ($topicName === SendInterface::TOPIC_NAME_SELL) {
                    // помечаем заказ, как KKM Fail
                    $entity = $this->requestHelper->getEntityByRequest($request);
                    $this->errorHelper->processKkmChequeRegistrationError($entity, $e);
                }

                // находим попытку, ставим флаг is_scheduled и заполняем время scheduled_at
                $this->attemptHelper->scheduleNextAttempt($request, $topicName);
            } else {
                $request->setIgnoreTrialsNum(false);
                $this->publisher->publish($topicName, $request);
            }
        } catch (\Throwable $e) {
            $entity = $this->requestHelper->getEntityByRequest($request);
            $this->errorHelper->processKkmChequeRegistrationError($entity, $e);
            if ($topicName === SendInterface::TOPIC_NAME_SELL && $this->helper->isRetrySendingEndlessly()) {
                // находим попытку, ставим флаг is_scheduled и заполняем время scheduled_at на следующей день
                $this->attemptHelper->scheduleNextAttempt(
                    $request,
                    SendInterface::TOPIC_NAME_SELL,
                    (new \DateTime('+1 day'))->format('Y-m-d H:i:s')
                );
            }
        }
    }
}
