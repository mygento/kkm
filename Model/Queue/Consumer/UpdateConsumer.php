<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue\Consumer;

use Mygento\Kkm\Api\Data\UpdateRequestInterface;
use Mygento\Kkm\Api\Processor\UpdateInterface;
use Mygento\Kkm\Exception\VendorBadServerAnswerException;
use Mygento\Kkm\Exception\VendorNonFatalErrorException;

class UpdateConsumer extends AbstractConsumer
{
    /**
     * @param \Mygento\Kkm\Api\Queue\MergedUpdateRequestInterface $mergedUpdateRequest
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendMergedRequest($mergedUpdateRequest)
    {
        $updateRequests = $mergedUpdateRequest->getRequests();
        $this->helper->debug(count($updateRequests) . ' UpdateRequests received to process.');

        /** @var UpdateRequestInterface $updateRequest */
        foreach ($updateRequests as $updateRequest) {
            $this->sendUpdateRequest($updateRequest);
        }
    }

    /**
     * @param UpdateRequestInterface $updateRequest
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function sendUpdateRequest(UpdateRequestInterface $updateRequest)
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
}
