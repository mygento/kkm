<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue\Consumer;

use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Processor\SendInterface;
use Mygento\Kkm\Exception\VendorBadServerAnswerException;
use Mygento\Kkm\Exception\VendorNonFatalErrorException;

class ResellConsumer extends AbstractConsumer
{
    /**
     * @param \Mygento\Kkm\Api\Queue\MergedRequestInterface $mergedRequest
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendMergedRequest($mergedRequest)
    {
        $requests = $mergedRequest->getRequests();
        $this->helper->debug(count($requests) . ' ResellRequests received to process.');

        foreach ($requests as $request) {
            $this->sendResellRequest($request);
        }
    }

    /**
     * @param RequestInterface $request
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function sendResellRequest($request)
    {
        //TODO: Test it

        try {
            $this->vendor->sendResellRequest($request);
        } catch (VendorNonFatalErrorException $e) {
            $this->helper->info($e->getMessage());

            $request->setIgnoreTrialsNum(false);
            $this->increaseExternalId($request);
            $this->publisher->publish(SendInterface::TOPIC_NAME_RESELL, $request);
        } catch (VendorBadServerAnswerException $e) {
            $this->helper->critical($e->getMessage());

            if ($this->helper->isUseCustomRetryIntervals()) {
                // находим попытку, ставим флаг is_scheduled и заполняем время scheduled_at.
                $this->attemptHelper->scheduleNextAttempt($request, SendInterface::TOPIC_NAME_RESELL);
            } else {
                $request->setIgnoreTrialsNum(false);
                $this->publisher->publish(SendInterface::TOPIC_NAME_RESELL, $request);
            }
        } catch (\Throwable $e) {
            $entity = $this->requestHelper->getEntityByRequest($request);
            $this->errorHelper->processKkmChequeRegistrationError($entity, $e);
        }
    }
}
