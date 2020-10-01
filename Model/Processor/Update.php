<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Processor;

use Magento\Framework\MessageQueue\PublisherInterface;
use Mygento\Kkm\Api\Data\ResponseInterface;
use Mygento\Kkm\Api\Data\UpdateRequestInterface;
use Mygento\Kkm\Api\Processor\SendInterface;
use Mygento\Kkm\Api\Processor\UpdateInterface;
use Mygento\Kkm\Helper\Request;
use Mygento\Kkm\Helper\Resell as ResellHelper;
use Mygento\Kkm\Model\VendorInterface;

class Update implements UpdateInterface
{
    /**
     * @var \Mygento\Kkm\Model\VendorInterface
     */
    protected $vendor;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $publisher;

    /**
     * @var \Mygento\Kkm\Helper\Resell
     */
    private $resellHelper;

    /**
     * @var \Mygento\Kkm\Helper\Request
     */
    private $requestHelper;

    /**
     * @var \Mygento\Kkm\Api\Processor\SendInterface
     */
    private $processor;

    /**
     * Processor constructor.
     * @param VendorInterface $vendor
     * @param \Mygento\Kkm\Api\Processor\SendInterface $processor
     * @param \Mygento\Kkm\Helper\Resell $resellHelper
     * @param \Mygento\Kkm\Helper\Request $requestHelper
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     */
    public function __construct(
        VendorInterface $vendor,
        SendInterface $processor,
        ResellHelper $resellHelper,
        Request $requestHelper,
        PublisherInterface $publisher
    ) {
        $this->vendor = $vendor;
        $this->publisher = $publisher;
        $this->resellHelper = $resellHelper;
        $this->requestHelper = $requestHelper;
        $this->processor = $processor;
    }

    /**
     * @inheritDoc
     */
    public function proceedSync(string $uuid): ResponseInterface
    {
        return $this->proceed($uuid);
    }

    /**
     * @inheritDoc
     */
    public function proceedAsync(UpdateRequestInterface $updateRequest): bool
    {
        $this->publisher->publish(UpdateInterface::TOPIC_NAME_UPDATE, $updateRequest);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function proceedUsingAttempt(string $uuid): ResponseInterface
    {
        return $this->proceed($uuid, true);
    }

    /**
     * @param $uuid
     * @param false $useAttempt
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @throws \Mygento\Kkm\Exception\VendorNonFatalErrorException
     * @return \Mygento\Kkm\Api\Data\ResponseInterface
     */
    protected function proceed($uuid, $useAttempt = false): ResponseInterface
    {
        $response = $this->vendor->updateStatus($uuid, $useAttempt);
        $entity = $this->requestHelper->getEntityByUuid($uuid);

        //Если был совершен refund по инвойсу - следовательно, это коррекция чека
        //и нужно заново отправить инвойс в АТОЛ
        if ($this->resellHelper->isNeededToResendSell($entity, $response)) {
            $this->processor->proceedResellSell($entity);
        }

        return $response;
    }
}
