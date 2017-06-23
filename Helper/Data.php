<?php
/**
 * @author Mygento
 * @copyright See COPYING.txt for license details.
 * @package Mygento_Kkm
 */
namespace Mygento\Kkm\Helper;

/**
 * Class Data
 */
class Data extends \Mygento\Base\Helper\Data
{

    /** @var string */
    protected $_code = 'kkm';

    /** @var \Magento\Sales\Model\Order\InvoiceFactory */
    protected $_orderInvoiceFactory;

    /** @var \Magento\Sales\Api\CreditmemoRepositoryInterface */
    protected $_creditmemoRepository;

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $_messageManager;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Mygento\Base\Model\Logger\LoggerFactory $loggerFactory
     * @param \Mygento\Base\Model\Logger\HandlerFactory $handlerFactory
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Sales\Model\Order\InvoiceFactory $orderInvoiceFactory
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Mygento\Base\Model\Logger\LoggerFactory $loggerFactory,
        \Mygento\Base\Model\Logger\HandlerFactory $handlerFactory,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\Order\InvoiceFactory $orderInvoiceFactory,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
    
        parent::__construct(
            $context,
            $loggerFactory,
            $handlerFactory,
            $encryptor,
            $curl,
            $storeManager
        );
        $this->_orderInvoiceFactory  = $orderInvoiceFactory;
        $this->_creditmemoRepository = $creditmemoRepository;
        $this->_messageManager       = $messageManager;
    }

    /**
     * @return object \Magento\Framework\Message\ManagerInterface
     */
    public function getMessageManager()
    {
        return $this->_messageManager;
    }

    /**
     * @param mixed $json
     * @param \Mygento\Kkm\Model\StatusFactory $status
     * @param string constants $vendor
     * @return boolean
     */
    public function updateKkmInfoInOrder($json, $status, $vendor = 'atol')
    {
        $incrementId = $status->getIncrementId();
        $entityType  = $status->getType();
        $entity      = null;

        if ($entityType === 'invoice') {
            $entity = $this->_orderInvoiceFactory->create()->load($incrementId, 'increment_id');
        } elseif ($entityType === 'creditmemo') {
            $entity = $this->_creditmemoRepository->create()->load($incrementId, 'increment_id');
        }

        if (!$entity || empty($incrementId) || !is_numeric($incrementId) || !$entity->getId()) {
            $this->addLog(
                "Error. Can not save callback info to order. Method params: Json = {$json} 
        Extrnal_id = {$externalId}. Incrememnt_id = {$incrementId}. Entity_type = {$entityType}",
                \Zend\Log\Logger::ERR
            );

            return false;
        }

        $this->saveTransactionInfoToOrder(
            $json,
            $entity,
            $entity->getOrder(),
            'Received message from KKM vendor.',
            $vendor
        );
    }

    /**
     * Save info about transaction to order
     * @param $getRequest string with json from vendor
     * @param $entity \Magento\Sales\Model\Order\InvoiceFactory | \Magento\Sales\Api\CreditmemoRepositoryInterface
     * @param $order \Magento\Sales\Model\Order
     * @return boolean
     */
    public function saveTransactionInfoToOrder(
        $getRequest,
        $entity,
        $order,
        $orderComment = '',
        $vendorName = 'atol'
    ) {
    
        $status = false;

        try {
            $getRequestObj = json_decode($getRequest);

            if ($getRequestObj->error == null) {
                $orderComment = $orderComment ?: 'Cheque has been sent to KKM vendor.';
                $comment      = '[' . strtoupper($vendorName) . '] '
                    . __($orderComment) . ' '
                    . ucwords($entity->getEntityType()) . ': '
                    . $entity->getIncrementId()
                    . '. Status: '
                    . ucwords($getRequestObj->status)
                    . '. Uuid: '
                    . $getRequestObj->uuid ?: 'no uuid';
            } else {
                $orderComment = $orderComment ?: 'Cheque has been rejected by KKM vendor.';
                $comment      = '[' . strtoupper($vendorName) . '] '
                    . __($orderComment) . ' '
                    . ucwords($entity->getEntityType()) . ': '
                    . $entity->getIncrementId()
                    . '. Status: '
                    . ucwords($getRequestObj->status)
                    . '. Error code: '
                    . $getRequestObj->error->code
                    . '. Error text: '
                    . $getRequestObj->error->text
                    . '. Uuid: '
                    . $getRequestObj->uuid ?: 'no uuid';

                if ($this->getConfig('mygento_kkm/general/fail_status')) {
                    $status = $this->getConfig('mygento_kkm/general/fail_status');
                }
            }

            if ($status) {
                $order->setState('processing', $status, $comment);
            } else {
                $order->addStatusHistoryComment($comment);
            }

            $order->save();
        } catch (\Exception $e) {
            $this->addLog(
                'Can not save KKM transaction info to order. Reason: ' . $e->getMessage(),
                \Zend\Log\Logger::CRIT
            );

            return false;
        }
    }
}
