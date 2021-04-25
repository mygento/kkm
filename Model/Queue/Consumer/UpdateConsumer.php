<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue\Consumer;

use Mygento\Kkm\Api\Data\UpdateRequestInterface;

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
            $this->getConsumerProcessor($updateRequest->getEntityStoreId())->processUpdate($updateRequest);
        }
    }
}
