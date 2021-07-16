<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use Mygento\Kkm\Exception\CreateDocumentFailedException;

/**
 * Class Error to handle Atol flow errors and fails
 * @package Mygento\Kkm\Helper
 */
class Error
{
    const ORDER_KKM_FAILED_STATUS = 'kkm_failed';

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $baseHelper;

    /**
     * @var \Magento\Framework\Notification\NotifierInterface
     */
    private $adminNotifier;

    /**
     * Error constructor.
     * @param \Mygento\Kkm\Helper\Data $baseHelper
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Notification\NotifierInterface $adminNotifier
     */
    public function __construct(
        \Mygento\Kkm\Helper\Data $baseHelper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Notification\NotifierInterface $adminNotifier
    ) {
        $this->orderRepository = $orderRepository;
        $this->baseHelper = $baseHelper;
        $this->adminNotifier = $adminNotifier;
    }

    /**
     * Makes different notifications if cheque was not successfully sent to KKM
     * @param \Magento\Sales\Api\Data\EntityInterface $entity
     * @param \Throwable|null $exception
     */
    public function processKkmChequeRegistrationError($entity, \Throwable $exception = null)
    {
        try {
            $entityType = ucfirst($entity->getEntityType());

            $fullMessage = $exception->getMessage() . ' ';
            $fullMessage .= "{$entityType}: {$entity->getIncrementId()}. ";
            $fullMessage .= "Order: {$entity->getOrder()->getIncrementId()}";

            $uuid =
                method_exists($exception, 'getResponse') && $exception->getResponse()
                    ? $exception->getResponse()->getUuid()
                    : null;

            if ($exception instanceof CreateDocumentFailedException) {
                $this->baseHelper->error('Params:', $exception->getDebugData());
                $this->baseHelper->error('Response: ' . $exception->getResponse());
                $fullMessage .= $uuid ? ". Transaction Id (uuid): {$uuid}" : '';
            }
            $this->baseHelper->error($fullMessage);
            $this->baseHelper->debug($exception->getTraceAsString());

            //Show Admin Messages
            if ($this->baseHelper->getConfig('general/admin_notifications')) {
                $this->adminNotifier->addMajor(
                    __(
                        'KKM Cheque sending error. Order: %1',
                        $entity->getOrder()->getIncrementId()
                    ),
                    $fullMessage
                );
            }

            $failStatus = $this->baseHelper->getOrderStatusAfterKkmFail();

            $order = $entity->getOrder();
            $order->addStatusToHistory(
                $failStatus ?: false,
                $fullMessage
            );
            $this->orderRepository->save($order);
        } catch (\Throwable $e) {
            $this->baseHelper->error($e->getMessage());
        }
    }
}
