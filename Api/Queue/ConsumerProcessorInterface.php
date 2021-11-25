<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api\Queue;

use Mygento\Kkm\Api\Data\UpdateRequestInterface;

interface ConsumerProcessorInterface
{
    public function processSell(QueueMessageInterface $queueMessage): void;

    public function processRefund(QueueMessageInterface $queueMessage): void;

    public function processResell(QueueMessageInterface $queueMessage): void;

    public function processUpdate(UpdateRequestInterface $updateRequest): void;
}
