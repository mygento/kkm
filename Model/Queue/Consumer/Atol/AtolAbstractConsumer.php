<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue\Consumer\Atol;

abstract class AtolAbstractConsumer
{
    /**
     * @var \Mygento\Kkm\Model\Processor\Update
     */
    protected $updateProcessor;

    /**
     * @var \Mygento\Kkm\Model\Atol\Vendor
     */
    protected $vendor;

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $publisher;

    /**
     * @var \Mygento\Kkm\Helper\Request
     */
    protected $requestHelper;

    /**
     * @var \Mygento\Kkm\Helper\Error
     */
    protected $errorHelper;

    /**
     * @var \Mygento\Kkm\Helper\TransactionAttempt
     */
    protected $attemptHelper;

    /**
     * @var \Mygento\Kkm\Helper\OrderComment
     */
    protected $orderComment;

    /**
     * @param \Mygento\Kkm\Model\Atol\Vendor $vendor
     * @param \Mygento\Kkm\Helper\Data $helper
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     * @param \Mygento\Kkm\Helper\Request $requestHelper
     * @param \Mygento\Kkm\Helper\Error $errorHelper
     * @param \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper
     * @param \Mygento\Kkm\Model\Processor\Update $updateProcessor
     * @param \Mygento\Kkm\Helper\OrderComment $orderComment
     */
    public function __construct(
        \Mygento\Kkm\Model\Atol\Vendor $vendor,
        \Mygento\Kkm\Helper\Data $helper,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Mygento\Kkm\Helper\Request $requestHelper,
        \Mygento\Kkm\Helper\Error $errorHelper,
        \Mygento\Kkm\Helper\TransactionAttempt $attemptHelper,
        \Mygento\Kkm\Model\Processor\Update $updateProcessor,
        \Mygento\Kkm\Helper\OrderComment $orderComment
    ) {
        $this->vendor = $vendor;
        $this->helper = $helper;
        $this->publisher = $publisher;
        $this->requestHelper = $requestHelper;
        $this->errorHelper = $errorHelper;
        $this->attemptHelper = $attemptHelper;
        $this->updateProcessor = $updateProcessor;
        $this->orderComment = $orderComment;
    }
}
