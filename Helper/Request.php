<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as ProductType;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\EntityInterface;
use Mygento\Kkm\Api\Data\RequestInterface;

/**
 * Class Request
 * @package Mygento\Kkm\Helper
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Request
{
    /**
     * @var Transaction
     */
    private $transactionHelper;

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
     * @var \Mygento\Kkm\Api\Queue\QueueMessageInterfaceFactory
     */
    private $queueMessageFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $kkmHelper;

    /**
     * Request constructor.
     * @param Transaction $transactionHelper
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Mygento\Kkm\Api\Queue\QueueMessageInterfaceFactory $queueMessageFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     */
    public function __construct(
        \Mygento\Kkm\Helper\Transaction $transactionHelper,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Mygento\Kkm\Api\Queue\QueueMessageInterfaceFactory $queueMessageFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Mygento\Kkm\Helper\Data $kkmHelper
    ) {
        $this->transactionHelper = $transactionHelper;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->orderRepository = $orderRepository;
        $this->queueMessageFactory = $queueMessageFactory;
        $this->productRepository = $productRepository;
        $this->kkmHelper = $kkmHelper;
    }

    /**
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return CreditmemoInterface|InvoiceInterface|OrderInterface
     */
    public function getEntityByRequest($request)
    {
        return $this->getEntityByIdAndOperationType($request->getSalesEntityId(), $request->getOperationType());
    }

    /**
     * @param \Mygento\Kkm\Api\Data\UpdateRequestInterface $updateRequest
     * @throws \Exception
     * @return CreditmemoInterface|InvoiceInterface|OrderInterface
     */
    public function getEntityByUpdateRequest($updateRequest)
    {
        return $this->getEntityByUuid($updateRequest->getUuid());
    }

    /**
     * @param string $uuid
     * @throws \Exception
     * @return CreditmemoInterface|InvoiceInterface|OrderInterface
     */
    public function getEntityByUuid($uuid)
    {
        $transaction = $this->transactionHelper->getTransactionByTxnId($uuid);
        if (!$transaction->getTransactionId()) {
            throw new \Exception("Transaction not found. Uuid: {$uuid}");
        }

        /** @var CreditmemoInterface|InvoiceInterface $entity */
        $entity = $this->transactionHelper->getEntityByTransaction($transaction);
        if (!$entity->getEntityId()) {
            throw new \Exception("Entity not found. Uuid: {$uuid}");
        }

        return $entity;
    }

    /**
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     */
    public function increaseExternalId($request)
    {
        if (preg_match('/^(.*)__(\d+)$/', $request->getExternalId(), $matches)) {
            $request->setExternalId($matches[1] . '__' . ($matches[2] + 1));
        } else {
            $request->setExternalId($request->getExternalId() . '__1');
        }
    }

    /**
     * @param \Magento\Sales\Model\EntityInterface $entity Order|Invoice|Creditmemo
     * @param string $postfix
     * @return string
     */
    public function generateExternalId(EntityInterface $entity, $postfix = '')
    {
        $postfix = $postfix ? "_{$postfix}" : '';

        return $entity->getEntityType() . '_' . $entity->getStoreId() . '_' . $entity->getIncrementId() . $postfix;
    }

    /**
     * @param \Mygento\Kkm\Api\Data\RequestInterface $request
     * @return \Mygento\Kkm\Api\Queue\QueueMessageInterface
     */
    public function getQueueMessage($request)
    {
        /** @var \Mygento\Kkm\Api\Queue\QueueMessageInterface $message */
        $message = $this->queueMessageFactory->create();
        $message
            ->setEntityId($request->getSalesEntityId())
            ->setEntityStoreId($request->getEntityStoreId())
            ->setOperationType($request->getOperationType());

        return $message;
    }

    /**
     * @param int|string $entityId
     * @param int|string $operationType
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return CreditmemoInterface|InvoiceInterface|OrderInterface
     */
    public function getEntityByIdAndOperationType($entityId, $operationType)
    {
        switch ($operationType) {
            case RequestInterface::SELL_OPERATION_TYPE:
            case RequestInterface::RESELL_REFUND_OPERATION_TYPE:
            case RequestInterface::RESELL_SELL_OPERATION_TYPE:
                return $this->invoiceRepository->get($entityId);
            case RequestInterface::REFUND_OPERATION_TYPE:
                return $this->creditmemoRepository->get($entityId);
            default:
                return $this->orderRepository->get($entityId);
        }
    }

    /**
     * Check does the item works as gift card. For Magento Commerce only
     * @param CreditmemoInterface|InvoiceInterface $salesEntity
     * @param string $itemName
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return bool
     */
    public function isGiftCard($salesEntity, $itemName)
    {
        $items = $salesEntity->getAllVisibleItems() ?? $salesEntity->getAllItems();

        if (!defined('ProductType::TYPE_GIFTCARD')) {
            return false;
        }

        foreach ($items as $item) {
            $productType = $item->getProductType()
                ?? $this->productRepository->getById($item->getProductId())->getTypeId();

            $giftCardType = ProductType::TYPE_GIFTCARD;
            if (strpos($item->getName(), $itemName) !== false && $productType == $giftCardType) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $marking
     * @param string|null $storeId
     * @return string
     */
    public function convertMarkingToHex(string $marking, $storeId = null): string
    {
        $unformattedHex = $this->getUnformattedHex($marking, $storeId);

        return trim(chunk_split($unformattedHex, 2, ' '));
    }

    /**
     * @param string $marking
     * @param string|null $storeId
     * @return string
     */
    public function convertMarkingToHexAndEncodeToBase64(string $marking, $storeId = null): string
    {
        $unformattedHex = $this->getUnformattedHex($marking, $storeId);

        return base64_encode(pack('H*', $unformattedHex));
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @return bool
     */
    public function isGiftCardApplied($entity)
    {
        $giftCardAmnt = $entity->getGiftCardsAmount() ?? $entity->getOrder()->getGiftCardsAmount();

        return $giftCardAmnt > 0.00;
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @return bool
     */
    public function isCustomerBalanceApplied($entity)
    {
        $customerBalanceAmount = $entity->getCustomerBalanceAmount()
            ?? $entity->getOrder()->getCustomerBalanceAmount();

        return $customerBalanceAmount > 0.00;
    }

    /**
     * @param string $marking
     * @param string|null $storeId
     * @return string
     */
    private function getUnformattedHex(string $marking, $storeId = null): string
    {
        $productCode = '444D';
        $gtin = substr($marking, 2, $this->kkmHelper->getConfig('marking/gtin_length', $storeId));
        $serialNumber = substr(
            $marking,
            2 + $this->kkmHelper->getConfig('marking/gtin_length', $storeId) + 2
        );
        $gtinHex = $this->normalizeHex(dechex($gtin));
        $serialHex = $this->normalizeHex(bin2hex($serialNumber));

        return strtoupper($productCode . $gtinHex . $serialHex);
    }

    /**
     * @param string $hex
     * @return string
     */
    private function normalizeHex(string $hex): string
    {
        if (strlen($hex) % 2 > 0) {
            $hex = '0' . $hex;
        }

        return $hex;
    }
}
