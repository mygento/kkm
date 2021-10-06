<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue\Consumer\Atol;

use Mygento\Kkm\Api\Data\UpdateRequestInterface;
use Mygento\Kkm\Api\Processor\UpdateInterface;
use Mygento\Kkm\Exception\VendorBadServerAnswerException;
use Mygento\Kkm\Exception\VendorNonFatalErrorException;

class AtolUpdateConsumer extends AtolAbstractConsumer
{
    /**
     * @param UpdateRequestInterface $updateRequest
     * @throws \Exception
     */
    public function sendUpdateRequest(UpdateRequestInterface $updateRequest)
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
