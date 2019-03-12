<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use \Mygento\Kkm\Api\Data\RequestInterface;
use \Magento\Sales\Api\Data\CreditmemoInterface;
use \Magento\Sales\Api\Data\InvoiceInterface;
use \Magento\Sales\Api\Data\OrderInterface;

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

    public function __construct(
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->creditmemoRepository = $creditmemoRepository;
        $this->invoiceRepository    = $invoiceRepository;
        $this->orderRepository      = $orderRepository;
    }

    /**
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     * @return CreditmemoInterface|InvoiceInterface|OrderInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
}