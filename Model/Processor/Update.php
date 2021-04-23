<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
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
use Mygento\Kkm\Helper\Data as KkmHelper;

class Update implements UpdateInterface
{
    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    private $publisher;

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
     * @var \Mygento\Kkm\Helper\Data
     */
    private $kkmHelper;

    /**
     * Processor constructor.
     * @param \Mygento\Kkm\Api\Processor\SendInterface $processor
     * @param \Mygento\Kkm\Helper\Resell $resellHelper
     * @param \Mygento\Kkm\Helper\Request $requestHelper
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     */
    public function __construct(
        SendInterface $processor,
        ResellHelper $resellHelper,
        Request $requestHelper,
        PublisherInterface $publisher,
        KkmHelper $kkmHelper
    ) {
        $this->publisher = $publisher;
        $this->resellHelper = $resellHelper;
        $this->requestHelper = $requestHelper;
        $this->processor = $processor;
        $this->kkmHelper = $kkmHelper;
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
     * @param string $uuid
     * @param bool $useAttempt
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @throws \Mygento\Kkm\Exception\VendorNonFatalErrorException
     * @return \Mygento\Kkm\Api\Data\ResponseInterface
     */
    protected function proceed($uuid, $useAttempt = false): ResponseInterface
    {
        $entity = $this->requestHelper->getEntityByUuid($uuid);
        $vendor = $this->kkmHelper->getCurrentVendor($entity->getStoreId());
        $response = $vendor->updateStatus($uuid, $useAttempt);

        //Если был совершен refund по инвойсу - следовательно, это коррекция чека
        //и нужно заново отправить инвойс в АТОЛ
        if ($this->resellHelper->isNeededToResendSell($entity, $response)) {
            $this->processor->proceedResellSell($entity);
        }

        return $response;
    }
}
