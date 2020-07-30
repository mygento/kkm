<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue\Consumer;

use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Exception\VendorBadServerAnswerException;
use Mygento\Kkm\Exception\VendorNonFatalErrorException;
use Mygento\Kkm\Model\Processor;

class RefundConsumer extends AbstractConsumer
{
    /**
     * @param \Mygento\Kkm\Api\Queue\MergedRequestInterface $mergedRequest
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendMergedRequest($mergedRequest)
    {
        $requests = $mergedRequest->getRequests();
        $this->helper->debug(count($requests) . ' RefundRequests received to process.');

        foreach ($requests as $request) {
            $this->sendRefundRequest($request);
        }
    }

    /**
     * @param RequestInterface $request
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function sendRefundRequest($request)
    {
        try {
            $this->vendor->sendRefundRequest($request);
        } catch (VendorNonFatalErrorException $e) {
            $this->helper->info($e->getMessage());

            $request->setIgnoreTrialsNum(false);
            $this->increaseExternalId($request);
            $this->publisher->publish(Processor::TOPIC_NAME_REFUND, $request);
        } catch (VendorBadServerAnswerException $e) {
            $this->helper->critical($e->getMessage());

            if ($this->helper->isUseCustomRetryIntervals()) {
                // находим попытку, ставим флаг is_scheduled и заполняем время scheduled_at.
                $this->attemptHelper->scheduleNextAttempt($request, Processor::TOPIC_NAME_REFUND);
            } else {
                $request->setIgnoreTrialsNum(false);
                $this->publisher->publish(Processor::TOPIC_NAME_REFUND, $request);
            }
        } catch (\Throwable $e) {
            $entity = $this->requestHelper->getEntityByRequest($request);
            $this->errorHelper->processKkmChequeRegistrationError($entity, $e);
        }
    }
}
