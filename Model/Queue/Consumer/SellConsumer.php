<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue\Consumer;

use Mygento\Kkm\Api\Processor\SendInterface;
use Mygento\Kkm\Exception\VendorBadServerAnswerException;
use Mygento\Kkm\Exception\VendorNonFatalErrorException;

class SellConsumer extends AbstractConsumer
{
    /**
     * @param \Mygento\Kkm\Api\Queue\MergedRequestInterface $mergedRequest
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\InvalidArgumentException
     */
    public function sendMergedRequest($mergedRequest)
    {
        //todo remove this log
        $this->helper->error('TEST: in sell');
        $requests = $mergedRequest->getRequests();
        $this->helper->debug(count($requests) . ' SellRequests received to process.');
        foreach ($requests as $request) {
            $request = unserialize($request);
            $this->getConsumerProcessor($request->getEntityStoreId())->processSell($request);
        }
    }
}
