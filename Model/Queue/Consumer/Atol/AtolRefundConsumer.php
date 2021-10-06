<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue\Consumer\Atol;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Processor\SendInterface;
use Mygento\Kkm\Exception\VendorBadServerAnswerException;
use Mygento\Kkm\Exception\VendorNonFatalErrorException;

class AtolRefundConsumer extends AtolAbstractConsumer
{
    /**
     * @param RequestInterface $request
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function sendRefundRequest(RequestInterface $request): void
    {
        try {
            $this->vendor->sendRefundRequest($request);
        } catch (VendorNonFatalErrorException $e) {
            $this->helper->info($e->getMessage());

            $request->setIgnoreTrialsNum(false);
            $this->requestHelper->increaseExternalId($request);
            $this->publisher->publish(
                SendInterface::TOPIC_NAME_REFUND,
                $this->requestHelper->getQueueMessage($request)
            );
        } catch (VendorBadServerAnswerException $e) {
            $this->helper->critical($e->getMessage());
            $storeId = $request->getStoreId();
            if ($this->helper->isUseCustomRetryIntervals($storeId)) {
                // находим попытку, ставим флаг is_scheduled и заполняем время scheduled_at.
                $this->attemptHelper->scheduleNextAttempt($request, SendInterface::TOPIC_NAME_REFUND);
            } else {
                $request->setIgnoreTrialsNum(false);
                $this->publisher->publish(
                    SendInterface::TOPIC_NAME_REFUND,
                    $this->requestHelper->getQueueMessage($request)
                );
            }
        } catch (\Throwable $e) {
            $entity = $this->requestHelper->getEntityByRequest($request);
            $this->errorHelper->processKkmChequeRegistrationError($entity, $e);
        }
    }
}
