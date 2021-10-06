<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Queue\Consumer\Atol;

use Magento\Framework\Exception\InvalidArgumentException;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\UpdateRequestInterface;
use Mygento\Kkm\Api\Queue\ConsumerProcessorInterface;
use Mygento\Kkm\Api\Queue\QueueMessageInterface;

/**
 * Class ConsumerProcessor
 * @package Mygento\Kkm\Model\Queue\Consumer\Atol
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConsumerProcessor implements ConsumerProcessorInterface
{
    /**
     * @var \Mygento\Kkm\Model\Queue\Consumer\Atol\AtolSellConsumer
     */
    protected $sellConsumer;

    /**
     * @var \Mygento\Kkm\Model\Queue\Consumer\Atol\AtolRefundConsumer
     */
    protected $refundConsumer;

    /**
     * @var \Mygento\Kkm\Model\Queue\Consumer\Atol\AtolResellConsumer
     */
    protected $resellConsumer;

    /**
     * @var \Mygento\Kkm\Model\Queue\Consumer\Atol\AtolUpdateConsumer
     */
    protected $updateConsumer;

    /**
     * @var \Mygento\Kkm\Model\Atol\Vendor
     */
    private $vendor;

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $helper;

    /**
     * @var \Mygento\Kkm\Helper\Request
     */
    private $requestHelper;

    /**
     * @param \Mygento\Kkm\Model\Queue\Consumer\Atol\AtolSellConsumer $sellConsumer
     * @param \Mygento\Kkm\Model\Queue\Consumer\Atol\AtolRefundConsumer $refundConsumer
     * @param \Mygento\Kkm\Model\Queue\Consumer\Atol\AtolResellConsumer $resellConsumer
     * @param \Mygento\Kkm\Model\Queue\Consumer\Atol\AtolUpdateConsumer $updateConsumer
     * @param \Mygento\Kkm\Model\Atol\Vendor $vendor
     * @param \Mygento\Kkm\Helper\Data $helper
     * @param \Mygento\Kkm\Helper\Request $requestHelper
     */
    public function __construct(
        \Mygento\Kkm\Model\Queue\Consumer\Atol\AtolSellConsumer $sellConsumer,
        \Mygento\Kkm\Model\Queue\Consumer\Atol\AtolRefundConsumer $refundConsumer,
        \Mygento\Kkm\Model\Queue\Consumer\Atol\AtolResellConsumer $resellConsumer,
        \Mygento\Kkm\Model\Queue\Consumer\Atol\AtolUpdateConsumer $updateConsumer,
        \Mygento\Kkm\Model\Atol\Vendor $vendor,
        \Mygento\Kkm\Helper\Data $helper,
        \Mygento\Kkm\Helper\Request $requestHelper
    ) {
        $this->sellConsumer = $sellConsumer;
        $this->refundConsumer = $refundConsumer;
        $this->resellConsumer = $resellConsumer;
        $this->updateConsumer = $updateConsumer;
        $this->vendor = $vendor;
        $this->helper = $helper;
        $this->requestHelper = $requestHelper;
    }

    public function processSell(QueueMessageInterface $queueMessage): void
    {
        try {
            $entity = $this->requestHelper->getEntityByIdAndOperationType(
                $queueMessage->getEntityId(),
                $queueMessage->getOperationType()
            );

            switch ($queueMessage->getOperationType()) {
                case RequestInterface::RESELL_SELL_OPERATION_TYPE:
                    $request = $this->vendor->buildRequestForResellSell($entity);
                    break;
                case RequestInterface::SELL_OPERATION_TYPE:
                    $request = $this->vendor->buildRequest($entity);
            }

            if (!isset($request)) {
                throw new InvalidArgumentException(__(
                    'Invalid operation type received in topic "mygento.kkm.message.sell". Operation type: %1',
                    $queueMessage->getOperationType()
                ));
            }

            $this->sellConsumer->sendSellRequest($request);
        } catch (\Throwable $e) {
            $this->helper->critical($e->getMessage());
        }
    }

    public function processRefund(QueueMessageInterface $queueMessage): void
    {
        try {
            $entity = $this->requestHelper->getEntityByIdAndOperationType(
                $queueMessage->getEntityId(),
                $queueMessage->getOperationType()
            );

            $request = $this->vendor->buildRequest($entity);
            $this->refundConsumer->sendRefundRequest($request);
        } catch (\Throwable $e) {
            $this->helper->critical($e->getMessage());
        }
    }

    public function processResell(QueueMessageInterface $queueMessage): void
    {
        try {
            $entity = $this->requestHelper->getEntityByIdAndOperationType(
                $queueMessage->getEntityId(),
                $queueMessage->getOperationType()
            );

            $request = $this->vendor->buildRequestForResellRefund($entity);
            $this->resellConsumer->sendResellRequest($request);
        } catch (\Throwable $e) {
            $this->helper->critical($e->getMessage());
        }
    }

    public function processUpdate(UpdateRequestInterface $updateRequest): void
    {
        try {
            $this->updateConsumer->sendUpdateRequest($updateRequest);
        } catch (\Throwable $e) {
            $this->helper->critical($e->getMessage());
        }
    }
}
