<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue;

use Magento\Framework\MessageQueue\MergedMessageInterface;
use Magento\Framework\MessageQueue\MergedMessageInterfaceFactory;
use Magento\Framework\MessageQueue\MergerInterface;
use Mygento\Kkm\Api\Queue\MergedUpdateRequestInterface;

class UpdateMerger implements MergerInterface
{
    /**
     * @var \Magento\Framework\MessageQueue\MergedMessageInterfaceFactory
     */
    private $mergedMessageFactory;

    /**
     * @var \Mygento\Kkm\Api\Queue\MergedUpdateRequestInterfaceFactory
     */
    private $mergedUpdateRequestFactory;

    /**
     * Merger constructor.
     * @param \Mygento\Kkm\Api\Queue\MergedUpdateRequestInterfaceFactory $mergedUpdateRequestFactory
     * @param \Magento\Framework\MessageQueue\MergedMessageInterfaceFactory $mergedMessageFactory
     */
    public function __construct(
        \Mygento\Kkm\Api\Queue\MergedUpdateRequestInterfaceFactory $mergedUpdateRequestFactory,
        \Magento\Framework\MessageQueue\MergedMessageInterfaceFactory $mergedMessageFactory
    ) {
        $this->mergedMessageFactory = $mergedMessageFactory
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
                MergedMessageInterfaceFactory::class
            );
        $this->mergedUpdateRequestFactory = $mergedUpdateRequestFactory;
    }

    /**
     * @inheritdoc
     */
    public function merge(array $messageList)
    {
        $result = [];

        foreach ($messageList as $topicName => $topicMessages) {
            $messages = array_values($topicMessages);
            $messageIds = array_keys($topicMessages);

            /** @var MergedUpdateRequestInterface $mergedUpdateRequest */
            $mergedUpdateRequest = $this->mergedUpdateRequestFactory->create();
            $mergedUpdateRequest->setRequests($messages);

            /** @var MergedMessageInterface $mergedUpdateRequest */
            $mergedMessage = $this->mergedMessageFactory->create(
                [
                    'mergedMessage' => $mergedUpdateRequest,
                    'originalMessagesIds' => $messageIds,
                ]
            );

            $result[$topicName][] = $mergedMessage;
        }

        return $result;
    }
}
