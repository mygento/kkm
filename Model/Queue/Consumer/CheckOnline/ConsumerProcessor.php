<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue\Consumer\CheckOnline;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\UpdateRequestInterface;
use Mygento\Kkm\Api\Processor\SendInterface;
use Mygento\Kkm\Api\Queue\ConsumerProcessorInterface;
use Mygento\Kkm\Api\Queue\QueueMessageInterface;
use Mygento\Kkm\Exception\VendorBadServerAnswerException;
use Mygento\Kkm\Exception\VendorNonFatalErrorException;

/**
 * Class ConsumerProcessor
 * @package Mygento\Kkm\Model\Queue\Consumer\CheckOnline
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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
     * @var \Mygento\Kkm\Helper\Error
     */
    private $errorHelper;

    /**
     * @var \Mygento\Kkm\Helper\TransactionAttempt
     */
    private $attemptHelper;

    /**
     * @var \Mygento\Kkm\Helper\OrderComment
     */
    private $orderComment;

    /**
     * @param \Mygento\Kkm\Model\CheckOnline\Vendor $vendor
     * @param \Mygento\Kkm\Helper\Data $helper
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     * @param \Mygento\Kkm\Helper\Request $requestHelper
     * @param \Mygento\Kkm\Helper\Error $errorHelper
     * @param \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper
     * @param \Mygento\Kkm\Helper\OrderComment $orderComment
     */
    public function __construct(
        \Mygento\Kkm\Model\CheckOnline\Vendor $vendor,
        \Mygento\Kkm\Helper\Data $helper,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Mygento\Kkm\Helper\Request $requestHelper,
        \Mygento\Kkm\Helper\Error $errorHelper,
        \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper,
        \Mygento\Kkm\Helper\OrderComment $orderComment
    ) {
        $this->vendor = $vendor;
        $this->helper = $helper;
        $this->publisher = $publisher;
        $this->requestHelper = $requestHelper;
        $this->errorHelper = $errorHelper;
        $this->attemptHelper = $attemptHelper;
        $this->orderComment = $orderComment;
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     */
    public function processSell(QueueMessageInterface $queueMessage): void
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
        } catch (VendorNonFatalErrorException | VendorBadServerAnswerException $e) {
            $this->helper->warning($e->getMessage());

            if (!isset($request)) {
                throw new LocalizedException(__('There is no request to schedule next attempt'));
            }

            $entity = $this->requestHelper->getEntityByRequest($request);

            if ($this->helper->isUseCustomRetryIntervals($entity->getStoreId())) {
                // помечаем заказ, как KKM Fail
                $this->errorHelper->processKkmChequeRegistrationError($entity, $e);

                // находим попытку, ставим флаг is_scheduled и заполняем время scheduled_at
                $this->attemptHelper->scheduleNextAttempt($request, SendInterface::TOPIC_NAME_SELL);

                return;
            }

            $request->setIgnoreTrialsNum(false);
            $this->publisher->publish(
                SendInterface::TOPIC_NAME_SELL,
                $this->requestHelper->getQueueMessage($request)
            );
        } catch (\Throwable $e) {
            if (!isset($request)) {
                throw new LocalizedException(__('There is no request to schedule next attempt'));
            }

            $entity = $this->requestHelper->getEntityByRequest($request);

            $this->errorHelper->processKkmChequeRegistrationError($entity, $e);

            if ($this->helper->isRetrySendingEndlessly($entity->getStoreId())) {
                // находим попытку, ставим флаг is_scheduled и заполняем время scheduled_at на следующей день
                $this->attemptHelper->scheduleNextAttempt(
                    $request,
                    SendInterface::TOPIC_NAME_SELL,
                    (new \DateTime('+1 day'))->format('Y-m-d H:i:s')
                );
            }
        }
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     */
    public function processRefund(QueueMessageInterface $queueMessage): void
    {
        try {
            $entity = $this->requestHelper->getEntityByIdAndOperationType(
                $queueMessage->getEntityId(),
                $queueMessage->getOperationType()
            );

            $request = $this->vendor->buildRequest($entity);
            $this->vendor->sendRefundRequest($request, $entity);
        } catch (VendorNonFatalErrorException | VendorBadServerAnswerException $e) {
            $this->helper->warning($e->getMessage());

            if (!isset($request)) {
                throw new LocalizedException(__('There is no request to schedule next attempt'));
            }

            $entity = $this->requestHelper->getEntityByRequest($request);

            if ($e instanceof VendorBadServerAnswerException) {
                $this->orderComment->addCommentToOrderNoChangeStatus($entity, $e->getMessage());
            }

            if ($this->helper->isUseCustomRetryIntervals($entity->getStoreId())) {
                // находим попытку, ставим флаг is_scheduled и заполняем время scheduled_at
                $this->attemptHelper->scheduleNextAttempt($request, SendInterface::TOPIC_NAME_REFUND);

                return;
            }

            $request->setIgnoreTrialsNum(false);
            $this->publisher->publish(
                SendInterface::TOPIC_NAME_REFUND,
                $this->requestHelper->getQueueMessage($request)
            );
        } catch (\Throwable $e) {
            $entity = $this->requestHelper->getEntityByIdAndOperationType(
                $queueMessage->getEntityId(),
                $queueMessage->getOperationType()
            );
            $this->errorHelper->processKkmChequeRegistrationError($entity, $e);
        }
    }

    /**
     * @inheritDoc
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function processResell(QueueMessageInterface $queueMessage): void
    {
        try {
            $entity = $this->requestHelper->getEntityByIdAndOperationType(
                $queueMessage->getEntityId(),
                $queueMessage->getOperationType()
            );
            $request = $this->vendor->buildRequestForResellRefund($entity);
            $this->vendor->sendResellRequest($request);
        } catch (VendorNonFatalErrorException | VendorBadServerAnswerException $e) {
            $this->helper->warning($e->getMessage());

            if (!isset($request)) {
                throw new LocalizedException(__('There is no request to schedule next attempt'));
            }

            $entity = $this->requestHelper->getEntityByRequest($request);

            if ($this->helper->isUseCustomRetryIntervals($entity->getStoreId())) {
                // находим попытку, ставим флаг is_scheduled и заполняем время scheduled_at.
                $this->attemptHelper->scheduleNextAttempt($request, SendInterface::TOPIC_NAME_RESELL);

                return;
            }

            $this->publisher->publish(
                SendInterface::TOPIC_NAME_RESELL,
                $this->requestHelper->getQueueMessage($request)
            );
        } catch (InputException $exc) {
            $this->helper->error($exc->getMessage());
        } catch (\Throwable $e) {
            $entity = $this->requestHelper->getEntityByIdAndOperationType(
                $queueMessage->getEntityId(),
                $queueMessage->getOperationType()
            );
            $this->errorHelper->processKkmChequeRegistrationError($entity, $e);
        }
    }

    /**
     * @param UpdateRequestInterface $updateRequest
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function processUpdate(UpdateRequestInterface $updateRequest): void
    {
    }
}
