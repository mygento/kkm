<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue\Consumer;

class SellConsumer extends AbstractConsumer
{
    /**
     * @param \Mygento\Kkm\Api\Queue\MergedRequestInterface $mergedRequest
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\InvalidArgumentException
     */
    public function sendMergedRequest($mergedRequest)
    {
        $messages = $mergedRequest->getRequests();
        $this->helper->debug(count($messages) . ' SellRequests received to process.');
        foreach ($messages as $message) {
            $this->getConsumerProcessor($message->getEntityStoreId())->processSell($message);
        }
    }
}
