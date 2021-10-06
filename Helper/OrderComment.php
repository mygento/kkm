<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use Magento\Backend\Model\UrlInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config as OrderConfig;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\ResponseInterface;
use Mygento\Kkm\Model\Atol\Response;

class OrderComment
{
    const COMMENT_ADDED_TO_ORDER_FLAG = 'kkm_comment_added';

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Data
     */
    private $kkmHelper;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var OrderConfig
     */
    private $orderConfig;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Data $kkmHelper,
        UrlInterface $urlBuilder,
        OrderConfig $orderConfig
    ) {
        $this->orderRepository = $orderRepository;
        $this->kkmHelper = $kkmHelper;
        $this->urlBuilder = $urlBuilder;
        $this->orderConfig = $orderConfig;
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @param ResponseInterface $response
     * @param mixed|null $txnId
     * @param string $operation
     */
    public function addCommentToOrder($entity, ResponseInterface $response, $txnId = null, $operation = '')
    {
        $order = $entity->getOrder();

        if ($order->getData(self::COMMENT_ADDED_TO_ORDER_FLAG)) {
            return;
        }

        $comment = $this->buildComment($entity, $response->getMessage(), $txnId, $operation);

        if ($this->isNeedChangeStatusFromFailedToDone($response, $entity)) {
            $order->addCommentToStatusHistory(
                $comment,
                $this->resolveOrderStatus($order->getState(), $entity->getStoreId())
            );

            $this->orderRepository->save($order);

            return;
        }

        $order->addCommentToStatusHistory($comment);
        $order->setData(self::COMMENT_ADDED_TO_ORDER_FLAG, true);
        $this->orderRepository->save($order);
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @param string $text
     * @param mixed|null $txnId
     * @param string $operation
     */
    public function addCommentToOrderNoChangeStatus($entity, $text, $txnId = null, $operation = '')
    {
        $order = $entity->getOrder();
        $comment = $this->buildComment($entity, $text, $txnId, $operation);
        $order->addCommentToStatusHistory($comment);
        $this->orderRepository->save($order);
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @param string $text
     * @param mixed|null $txnId
     * @param string $operation
     * @return \Magento\Framework\Phrase
     */
    private function buildComment($entity, $text, $txnId = null, $operation = '')
    {
        $message = ucfirst($entity->getEntityType()) . ': ' . $entity->getIncrementId() . '. ';
        $message .= $text;

        if ($txnId) {
            $href =
                $this->urlBuilder->getUrl(
                    'sales/transactions/view',
                    [
                        'txn_id' => $txnId,
                    ]
                );

            $message .= " <a href='{$href}'>Transaction id: {$txnId}</a>";
        }

        $vendorCode = $this->kkmHelper->getCurrentVendorCode($entity->getStoreId());
        switch ($operation) {
            case RequestInterface::RESELL_REFUND_OPERATION_TYPE:
                $comment = __('[%1] Resell (refund) was sent. %2', $vendorCode, $message);
                break;
            case RequestInterface::RESELL_SELL_OPERATION_TYPE:
                $comment = __('[%1] Resell (sell) was sent. %2', $vendorCode, $message);
                break;
            default:
                $comment = __('[%1] Cheque was sent. %2', $vendorCode, $message);
        }

        return $comment;
    }

    private function resolveOrderStatus(string $orderState, string $storeId): string
    {
        return $orderState === Order::STATE_CLOSED
            ? $this->orderConfig->getStateDefaultStatus(Order::STATE_CLOSED)
            : $this->kkmHelper->getOrderStatusAfterKkmTransactionDone($storeId);
    }

    /**
     * @param ResponseInterface $response
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @return bool
     */
    private function isNeedChangeStatusFromFailedToDone(ResponseInterface $response, $entity): bool
    {
        $order = $entity->getOrder();

        return $response->getStatus() === Response::STATUS_DONE
            && $order->getStatus() === Error::ORDER_KKM_FAILED_STATUS
            && $this->kkmHelper->getOrderStatusAfterKkmTransactionDone($entity->getStoreId());
    }
}
