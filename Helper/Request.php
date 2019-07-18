<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mygento\Kkm\Api\Data\RequestInterface;

class Request
{
    /**
     * @var \Magento\Sales\Api\CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * @var \Magento\Sales\Api\InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Request constructor.
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->creditmemoRepository = $creditmemoRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return CreditmemoInterface|InvoiceInterface|OrderInterface
     */
    public function getEntityByRequest($request)
    {
        switch ($request->getOperationType()) {
            case RequestInterface::SELL_OPERATION_TYPE:
                return $this->invoiceRepository->get($request->getSalesEntityId());
                break;
            case RequestInterface::REFUND_OPERATION_TYPE:
                return $this->creditmemoRepository->get($request->getSalesEntityId());
                break;
            default:
                return $this->orderRepository->get($request->getSalesEntityId());
                break;
        }
    }

    /**
     * @param \Mygento\Kkm\Api\Data\UpdateRequestInterface $updateRequest
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return CreditmemoInterface|InvoiceInterface|OrderInterface
     */
    public function getEntityByUpdateRequest($updateRequest)
    {
        // TODO
    }
}
