<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue\Consumer\Atol;

use Magento\Framework\Exception\InputException;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Processor\SendInterface;
use Mygento\Kkm\Api\Processor\UpdateInterface;
use Mygento\Kkm\Api\Queue\ConsumerProcessorInterface;
use Mygento\Kkm\Exception\VendorBadServerAnswerException;
use Mygento\Kkm\Exception\VendorNonFatalErrorException;

class ConsumerProcessor implements ConsumerProcessorInterface
{
    /**
     * @var \Mygento\Kkm\Model\Atol\Vendor
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
     * @var \Mygento\Kkm\Model\Processor\Update
     */
    protected $updateProcessor;

    /**
     * ConsumerProcessor constructor.
     * @param \Mygento\Kkm\Model\Atol\Vendor $vendor
     * @param \Mygento\Kkm\Helper\Data $helper
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     * @param \Mygento\Kkm\Helper\Request $requestHelper
     * @param \Mygento\Kkm\Helper\Error\Proxy $errorHelper
     * @param \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper
     * @param \Mygento\Kkm\Model\Processor\Update $updateProcessor
     */
    public function __construct(
        \Mygento\Kkm\Model\Atol\Vendor $vendor,
        \Mygento\Kkm\Helper\Data $helper,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Mygento\Kkm\Helper\Request $requestHelper,
        \Mygento\Kkm\Helper\Error\Proxy $errorHelper,
        \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper,
        \Mygento\Kkm\Model\Processor\Update $updateProcessor
    ) {
        $this->vendor = $vendor;
        $this->helper = $helper;
        $this->publisher = $publisher;
        $this->requestHelper = $requestHelper;
        $this->errorHelper = $errorHelper;
        $this->attemptHelper = $attemptHelper;
        $this->updateProcessor = $updateProcessor;
    }

    /**
     * @inheritDoc
     */
    public function processSell($queueMessage)
    {
        $this->processSellAndRefund($queueMessage, SendInterface::TOPIC_NAME_SELL,);
    }

    /**
     * @inheritDoc
     */
    public function processRefund($queueMessage)
    {
        $this->processSellAndRefund($queueMessage, SendInterface::TOPIC_NAME_REFUND);
    }

    /**
     * @inheritDoc
     */
    public function processResell($queueMessage)
    {
        try {
            $entity = $this->requestHelper->getEntityByIdAndOperationType(
                $queueMessage->getEntityId(),
                $queueMessage->getOperationType()
            );
            $request = $this->vendor->buildRequestForResellRefund($entity);
            $this->vendor->sendResellRequest($request);
        } catch (VendorNonFatalErrorException $e) {
            $this->helper->info($e->getMessage());

            $request->setIgnoreTrialsNum(false);
            $this->requestHelper->increaseExternalId($request);
            $this->publisher->publish(
                SendInterface::TOPIC_NAME_RESELL,
                $this->requestHelper->getQueueMessage($request)
            );
        } catch (VendorBadServerAnswerException $e) {
            $this->helper->critical($e->getMessage());

            if ($this->helper->isUseCustomRetryIntervals($entity->getStoreId())) {
                // находим попытку, ставим флаг is_scheduled и заполняем время scheduled_at.
                $this->attemptHelper->scheduleNextAttempt($request, SendInterface::TOPIC_NAME_RESELL);
            } else {
                $request->setIgnoreTrialsNum(false);
                $this->publisher->publish(
                    SendInterface::TOPIC_NAME_RESELL,
                    $this->requestHelper->getQueueMessage($request)
                );
            }
        } catch (InputException $exc) {
            $this->helper->error($exc->getMessage());
        } catch (\Throwable $e) {
            $this->errorHelper->processKkmChequeRegistrationError($entity, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function processUpdate($updateRequest)
    {
        try {
            $response = $this->updateProcessor->proceedUsingAttempt($updateRequest->getUuid());
            if ($response->isWait()) {
                $this->publisher->publish(UpdateInterface::TOPIC_NAME_UPDATE, $updateRequest);
            }
        } catch (VendorNonFatalErrorException | VendorBadServerAnswerException $e) {
            $this->helper->info($e->getMessage());

            $this->publisher->publish(UpdateInterface::TOPIC_NAME_UPDATE, $updateRequest);
        } catch (\Throwable $e) {
            $entity = $this->requestHelper->getEntityByUpdateRequest($updateRequest);
            $this->errorHelper->processKkmChequeRegistrationError($entity, $e);
        }
    }

    /**
     * @param \Mygento\Kkm\Api\Queue\QueueMessageInterface $queueMessage
     * @param string $topicName
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function processSellAndRefund($queueMessage, $topicName)
    {
        try {
            $entity = $this->requestHelper->getEntityByIdAndOperationType(
                $queueMessage->getEntityId(),
                $queueMessage->getOperationType()
            );

            if ($queueMessage->getOperationType() === RequestInterface::RESELL_SELL_OPERATION_TYPE) {
                $request = $this->vendor->buildRequestForResellSell($entity);
            } else {
                $request = $this->vendor->buildRequest($entity);
            }

            $this->vendor->sendSellRequest($request, $entity);
        } catch (VendorNonFatalErrorException $e) {
            // меняем external_id и пробуем сделать повторную отправку
            $this->helper->info($e->getMessage());

            $request->setIgnoreTrialsNum(false);
            $this->requestHelper->increaseExternalId($request);
            $this->publisher->publish($topicName, $this->requestHelper->getQueueMessage($request));
        } catch (VendorBadServerAnswerException $e) {
            $this->helper->info($e->getMessage());

            if ($this->helper->isUseCustomRetryIntervals($entity->getStoreId())) {
                if ($topicName === SendInterface::TOPIC_NAME_SELL) {
                    // помечаем заказ, как KKM Fail
                    $this->errorHelper->processKkmChequeRegistrationError($entity, $e);
                }

                // находим попытку, ставим флаг is_scheduled и заполняем время scheduled_at
                $this->attemptHelper->scheduleNextAttempt($request, $topicName);
            } else {
                $request->setIgnoreTrialsNum(false);
                $this->publisher->publish($topicName, $this->requestHelper->getQueueMessage($request));
            }
        } catch (\Throwable $e) {
            $this->errorHelper->processKkmChequeRegistrationError($entity, $e);
            if ($topicName === SendInterface::TOPIC_NAME_SELL
                && $this->helper->isRetrySendingEndlessly($entity->getStoreId())
            ) {
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
