<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Request;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\EntityInterface;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Helper\Data as KkmHelperData;
use Mygento\Kkm\Helper\Transaction as TransactionHelper;
use Mygento\Kkm\Model\GetRecalculated;

/**
 * Class RequestBuilder
 * @package Mygento\Kkm\Model\Request
 */
abstract class AbstractRequestBuilder
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var KkmHelperData
     */
    protected $kkmHelper;

    /**
     * @var GetRecalculated
     */
    protected $getRecalculated;

    /**
     * @var TransactionHelper
     */
    protected $transactionHelper;

    /**
     * AbstractRequestBuilder constructor.
     * @param ProductRepositoryInterface $productRepository
     * @param KkmHelperData $kkmHelper
     * @param GetRecalculated $getRecalculated
     * @param TransactionHelper $transactionHelper
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        KkmHelperData $kkmHelper,
        GetRecalculated $getRecalculated,
        TransactionHelper $transactionHelper
    ) {
        $this->productRepository = $productRepository;
        $this->kkmHelper = $kkmHelper;
        $this->getRecalculated = $getRecalculated;
        $this->transactionHelper = $transactionHelper;
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface|OrderInterface $salesEntity
     * @param string $paymentMethod
     * @param string $shippingPaymentObject
     * @param array $receiptData
     * @param string $clientName
     * @param string $clientInn
     * @return RequestInterface
     */
    abstract public function buildRequest(
        $salesEntity,
        $paymentMethod = null,
        $shippingPaymentObject = null,
        array $receiptData = [],
        $clientName = '',
        $clientInn = ''
    ): RequestInterface;

    /**
     * @param InvoiceInterface $invoice
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return RequestInterface
     */
    public function buildRequestForResellRefund($invoice): RequestInterface
    {
        $request = $this->buildRequest($invoice);

        //Check is there a done transaction among entity transactions.
        $doneTransaction = $this->transactionHelper->getDoneTransaction($invoice);
        $lastRefundTransaction = $this->transactionHelper->getLastResellRefundTransaction($invoice);

        $externalId = $this->transactionHelper->getExternalId($doneTransaction)
            ?? $this->generateExternalId($invoice);
        $externalId .= '_refund';

        $externalId = $this->transactionHelper->getExternalId($lastRefundTransaction) ?? $externalId;

        $request->setExternalId($externalId);
        $request->setOperationType(RequestInterface::RESELL_REFUND_OPERATION_TYPE);

        return $request;
    }

    /**
     * @param InvoiceInterface $invoice
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return RequestInterface
     */
    public function buildRequestForResellSell($invoice): RequestInterface
    {
        $request = $this->buildRequest($invoice);

        //Check is there a done transaction among entity transactions.
        $doneTransaction = $this->transactionHelper->getDoneTransaction($invoice);

        $lastResellTransaction = $this->transactionHelper->getLastResellSellTransaction($invoice);

        $externalId = $this->transactionHelper->getExternalId($doneTransaction)
            ?? $this->generateExternalId($invoice);
        $externalId .= '_resell';

        $externalId = $this->transactionHelper->getExternalId($lastResellTransaction) ?? $externalId;

        $request->setExternalId($externalId);
        $request->setOperationType(RequestInterface::RESELL_SELL_OPERATION_TYPE);

        return $request;
    }

    /**
     * @param \Magento\Sales\Model\EntityInterface $entity Order|Invoice|Creditmemo
     * @param string $postfix
     * @return string
     */
    protected function generateExternalId(EntityInterface $entity, $postfix = '')
    {
        $postfix = $postfix ? "_{$postfix}" : '';

        return $entity->getEntityType() . '_' . $entity->getStoreId() . '_' . $entity->getIncrementId() . $postfix;
    }

    /**
     * Check does the item works as gift card. For Magento Commerce only
     * @param CreditmemoInterface|InvoiceInterface $salesEntity
     * @param string $itemName
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return bool
     */
    protected function isGiftCard($salesEntity, $itemName)
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
    protected function convertMarkingToHex(string $marking, $storeId = null): string
    {
        $unformattedHex = $this->getUnformattedHex($marking, $storeId);

        return trim(chunk_split($unformattedHex, 2, ' '));
    }

    /**
     * @param string $marking
     * @param string|null $storeId
     * @return string
     */
    protected function convertMarkingToHexAndEncodeToBase64(string $marking, $storeId = null): string
    {
        $unformattedHex = $this->getUnformattedHex($marking, $storeId);

        return base64_encode(pack('H*', $unformattedHex));
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @return bool
     */
    protected function isGiftCardApplied($entity)
    {
        $giftCardAmnt = $entity->getGiftCardsAmount() ?? $entity->getOrder()->getGiftCardsAmount();

        return $giftCardAmnt > 0.00;
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $entity
     * @return bool
     */
    protected function isCustomerBalanceApplied($entity)
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
    protected function getUnformattedHex(string $marking, $storeId = null): string
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
    protected function normalizeHex(string $hex): string
    {
        if (strlen($hex) % 2 > 0) {
            $hex = '0' . $hex;
        }

        return $hex;
    }
}
