<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use Mygento\Kkm\Exception\CreateDocumentFailedException;

/**
 * Class Data
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends \Mygento\Base\Helper\Data
{
    const CONFIG_CODE = 'mygento_kkm';
    const ORDER_KKM_FAILED_STATUS = 'kkm_failed';

    /** @var string */
    protected $code = 'mygento_kkm';

    /** @var \Magento\Sales\Model\Order\InvoiceFactory */
    private $orderInvoiceFactory;

    /** @var \Magento\Sales\Api\CreditmemoRepositoryInterface */
    private $creditmemoRepository;

    /** @var \Magento\Framework\Message\ManagerInterface */
    private $messageManager;
    /**
     * @var \Magento\Framework\Notification\NotifierInterface
     */
    private $adminNotifier;
    /**
     * @var \Magento\Framework\Url
     */
    private $urlHelper;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Data constructor.
     * @param \Mygento\Base\Model\LogManager $logManager
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Model\Order\InvoiceFactory $orderInvoiceFactory
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Url $urlHelper
     * @param \Magento\Framework\Notification\NotifierInterface $notifier
     */
    public function __construct(
        \Mygento\Base\Model\LogManager $logManager,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order\InvoiceFactory $orderInvoiceFactory,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Url $urlHelper,
        \Magento\Framework\Notification\NotifierInterface $notifier
    ) {
        parent::__construct(
            $logManager,
            $encryptor,
            $context
        );

        $this->orderInvoiceFactory  = $orderInvoiceFactory;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->messageManager       = $messageManager;
        $this->adminNotifier        = $notifier;
        $this->urlHelper            = $urlHelper;
        $this->orderRepository      = $orderRepository;
    }

    /**
     * @return \Magento\Framework\Message\ManagerInterface
     */
    public function getMessageManager()
    {
        return $this->messageManager;
    }

    /**
     * @param string $param
     * @return string
     */
    public function getConfig($param)
    {
        return parent::getConfig($this->getCode() . '/' . $param);
    }

    /**
     * @return string|null
     */
    public function getStoreEmail()
    {
        return parent::getConfig('trans_email/ident_general/email');
    }

    /**
     * @param string|null $routePath
     * @param array|null $routeParams
     * @return string
     */
    private function getFrontendUrl($routePath, $routeParams)
    {
        return $this->urlHelper->getUrl($routePath, $routeParams);
    }

    /**
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->getConfig('atol/callback_url')
            ?? $this->getFrontendUrl(
                'kkm/frontend/callback',
                ['_secure' => true]
            );
    }

    /**
     * @return bool
     */
    public function isTestMode()
    {
        return (bool)$this->getConfig('atol/test_mode');
    }

    /**
     * @return bool
     */
    public function isMessageQueueEnabled()
    {
        return (bool)$this->getConfig('general/async_enabled');
    }

    /**
     * Makes different notifications if cheque was not successfully sent to KKM
     * @param \Magento\Sales\Api\Data\EntityInterface $entity
     * @param \Exception|null $exception
     */
    public function processKkmChequeRegistrationError($entity, \Exception $exception = null)
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
                $this->error('Params:', $exception->getDebugData());
                $this->error('Response: ' . $exception->getResponse());
                $fullMessage .= $uuid ? ". Transaction Id (uuid): {$uuid}" : '';
            }
            $this->error($fullMessage);

            //Show Admin Messages
            if ($this->getConfig('general/admin_notifications')) {
                $this->adminNotifier->addMajor(
                    __(
                        'KKM Cheque sending error. Order: %1',
                        $entity->getOrder()->getIncrementId()
                    ),
                    $fullMessage
                );
            }

            $order = $entity->getOrder();
            $order->addStatusToHistory(
                self::ORDER_KKM_FAILED_STATUS,
                $fullMessage
            );
            $this->orderRepository->save($order);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
