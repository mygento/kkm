<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue;

use Magento\Framework\MessageQueue\MergedMessageInterface;
use Magento\Framework\MessageQueue\MergedMessageInterfaceFactory;
use Magento\Framework\MessageQueue\MergerInterface;
use Mygento\Kkm\Api\Queue\MergedRequestInterface;

class Merger implements MergerInterface
{
    /**
     * @var \Magento\Framework\MessageQueue\MergedMessageInterfaceFactory
     */
    private $mergedMessageFactory;
    /**
     * @var \Mygento\Kkm\Api\Queue\MergedRequestInterfaceFactory
     */
    private $mergedRequestFactory;

    /**
     * Merger constructor.
     * @param \Mygento\Kkm\Api\Queue\MergedRequestInterfaceFactory $mergedRequestFactory
     * @param \Magento\Framework\MessageQueue\MergedMessageInterfaceFactory $mergedMessageFactory
     */
    public function __construct(
        \Mygento\Kkm\Api\Queue\MergedRequestInterfaceFactory $mergedRequestFactory,
        \Magento\Framework\MessageQueue\MergedMessageInterfaceFactory $mergedMessageFactory
    ) {
        $this->mergedMessageFactory = $mergedMessageFactory
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
                MergedMessageInterfaceFactory::class
            );
        $this->mergedRequestFactory = $mergedRequestFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(array $messageList)
    {
        $result = [];

        foreach ($messageList as $topicName => $topicMessages) {
            $messages   = array_values($topicMessages);
            $messageIds = array_keys($topicMessages);

            /** @var MergedRequestInterface $mergedRequest */
            $mergedRequest = $this->mergedRequestFactory->create();
            $mergedRequest->setMessages($messages);

            /** @var MergedMessageInterface $mergedRequest */
            $mergedMessage = $this->mergedMessageFactory->create(
                [
                    'mergedMessage'       => $mergedRequest,
                    'originalMessagesIds' => $messageIds,
                ]
            );

            $result[$topicName][] = $mergedMessage;
        }

        return $result;
    }
}
