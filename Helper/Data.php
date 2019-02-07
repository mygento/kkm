<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use Mygento\Kkm\Exception\CreateDocumentFailedException;
use Psr\Log\LoggerInterface;

/**
 * Class Data
 */
class Data extends \Mygento\Base\Helper\Data implements LoggerInterface
{
    const CONFIG_CODE = 'mygento_kkm';

    /** @var string */
    protected $_code = 'mygento_kkm';

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

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Mygento\Base\Model\Logger\LoggerFactory $loggerFactory,
        \Mygento\Base\Model\Logger\HandlerFactory $handlerFactory,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order\InvoiceFactory $orderInvoiceFactory,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Url $urlHelper,
        \Magento\Framework\Notification\NotifierInterface $notifier
    ) {
        parent::__construct($context, $loggerFactory, $handlerFactory, $encryptor, $curl, $productRepository);

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

    public function getConfig($param)
    {
        return parent::getConfig($this->getCode() . '/' . $param);
    }

    public function getStoreEmail()
    {
        return parent::getConfig('trans_email/ident_general/email');
    }

    public function getFrontendUrl($routePath, $routeParams)
    {
        return $this->urlHelper->getUrl($routePath, $routeParams);
    }

    public function getCallbackUrl()
    {
        return $this->getConfig('atol/callback_url')
            ?? $this->getFrontendUrl(
                'kkm/frontend/callback',
                ['_secure' => true]
            );
    }

    /**
     *
     * @param string $message
     * @param array $context
     */
    public function alert($message, array $context = [])
    {
        $this->writeLog($message, \Monolog\Logger::ALERT);
    }

    /**
     *
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = [])
    {
        $this->writeLog($message, \Monolog\Logger::CRITICAL);
    }

    /**
     *
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = [])
    {
        $this->writeLog($message, \Monolog\Logger::DEBUG);
    }

    /**
     *
     * @param string $message
     * @param array $context
     */
    public function emergency($message, array $context = [])
    {
        $this->writeLog($message, \Monolog\Logger::EMERGENCY);
    }

    /**
     *
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = [])
    {
        $this->writeLog($message, \Monolog\Logger::ERROR);
    }

    /**
     *
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = [])
    {
        $this->writeLog($message, \Monolog\Logger::INFO);
    }

    /**
     *
     * @param string $message
     * @param array $context
     * @param mixed $level
     */
    public function log($level, $message, array $context = [])
    {
        $this->writeLog($message, $level);
    }

    /**
     *
     * @param string $message
     * @param array $context
     */
    public function notice($message, array $context = [])
    {
        $this->writeLog($message, \Monolog\Logger::NOTICE);
    }

    /**
     *
     * @param string $message
     * @param array $context
     */
    public function warning($message, array $context = [])
    {
        $this->writeLog($message, \Monolog\Logger::WARNING);
    }

    /**
     *
     * @param string|array $message
     * @param string $level
     */
    protected function writeLog($message, $level = \Monolog\Logger::DEBUG)
    {
        if (!parent::getConfig($this->getDebugConfigPath())) {
            return false;
        }

        if ($level < $this->getConfig('general/debug_level')) {
            return false;
        }

        if (is_array($message)) {
            // @codingStandardsIgnoreStart
            $message = print_r($message, true);
            // @codingStandardsIgnoreEnd
        }

        $this->_logger->log($level, $message);
    }

    public function isTestMode()
    {
        return $this->getConfig('atol/test_mode');
    }

    /** Makes different notifications if cheque was not successfully sent to KKM
     * @param \Magento\Sales\Api\Data\EntityInterface $entity
     * @param \Exception|null $exception
     */
    public function processKkmChequeRegistrationError($entity, \Exception $exception = null)
    {
        $entityType = ucfirst($entity->getEntityType());

        $fullMessage = $exception->getMessage() . ' ';
        $fullMessage .= "{$entityType}: {$entity->getIncrementId()}. ";
        $fullMessage .= "Order: {$entity->getOrder()->getIncrementId()}";

        $uuid = $exception->getResponse()
            ? $exception->getResponse()->getUuid()
            : null;

        $this->error($fullMessage);
        if ($exception instanceof CreateDocumentFailedException) {
            $this->error('Params:');
            $this->error($exception->getDebugData());
            $this->error('Response: ' . $exception->getResponse());
            $fullMessage .= $uuid ? ". Transaction Id (uuid): {$uuid}" : '';
        }

        //Show Admin Messages
        if ($this->getConfig('general/admin_notifications')) {
            $this->adminNotifier->addMajor(
                __('KKM Cheque sending error. Order: %1', $entity->getOrder()->getIncrementId()),
                $fullMessage
            );
        }

        try {
            $order = $entity->getOrder();
            $order->addStatusToHistory(
                \Mygento\Kkm\Model\AbstractModel::ORDER_KKM_FAILED_STATUS,
                $fullMessage
            );
            $this->orderRepository->save($order);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
