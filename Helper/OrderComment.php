<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\ResponseInterface;
use Mygento\Kkm\Model\Atol\Response;
use Mygento\Kkm\Helper\Data;
use Mygento\Kkm\Helper\Error;
use Magento\Backend\Model\UrlInterface;

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

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Data $kkmHelper,
        UrlInterface $urlBuilder
    ) {
        $this->orderRepository = $orderRepository;
        $this->kkmHelper = $kkmHelper;
        $this->urlBuilder = $urlBuilder;
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

        $message = ucfirst($entity->getEntityType()) . ': ' . $entity->getIncrementId() . '. ';
        $message .= $response->getMessage();

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

        if ($response->getStatus() == Response::STATUS_DONE
            && $order->getStatus() == Error::ORDER_KKM_FAILED_STATUS
            && $this->kkmHelper->getOrderStatusAfterKkmTransactionDone()
        ) {
            $order->addCommentToStatusHistory(
                $comment,
                $this->kkmHelper->getOrderStatusAfterKkmTransactionDone()
            );
        } else {
            $order->addCommentToStatusHistory($comment);
            $order->setData(self::COMMENT_ADDED_TO_ORDER_FLAG, true);
        }

        $this->orderRepository->save($order);
    }
}
