<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api\Queue;

interface ConsumerProcessorInterface
{
    /**
     * @param \Mygento\Kkm\Api\Queue\QueueMessageInterface $queueMessage
     */
    public function processSell($queueMessage);

    /**
     * @param \Mygento\Kkm\Api\Queue\QueueMessageInterface $queueMessage
     */
    public function processRefund($queueMessage);

    /**
     * @param \Mygento\Kkm\Api\Queue\QueueMessageInterface $queueMessage
     */
    public function processResell($queueMessage);

    /**
     * @param \Mygento\Kkm\Api\Data\UpdateRequestInterface $updateRequest
     */
    public function processUpdate($updateRequest);
}
