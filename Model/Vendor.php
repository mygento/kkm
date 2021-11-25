<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model;

use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\ResponseInterface;
use Mygento\Kkm\Helper\Data;
use Mygento\Kkm\Helper\Request;

class Vendor implements VendorInterface, StatusUpdatable
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Request
     */
    private $requestHelper;

    /**
     * @var VendorInterface[]
     */
    private $vendors;

    public function __construct(
        Data $helper,
        Request $requestHelper,
        $vendors = []
    ) {
        $this->helper = $helper;
        $this->requestHelper = $requestHelper;
        $this->vendors = $vendors;
    }

    /**
     * @inheritDoc
     */
    public function sendSellRequest($request, $invoice = null)
    {
        $currentVendor = $this->getCurrentVendor($invoice ? $invoice->getStoreId() : null);

        return $currentVendor->sendSellRequest($request, $invoice);
    }

    /**
     * @inheritDoc
     */
    public function sendRefundRequest($request, $creditmemo = null)
    {
        $currentVendor = $this->getCurrentVendor($creditmemo ? $creditmemo->getStoreId() : null);

        return $currentVendor->sendRefundRequest($request, $creditmemo);
    }

    /**
     * @inheritDoc
     */
    public function sendResellRequest(RequestInterface $request, ?InvoiceInterface $invoice = null): ResponseInterface
    {
        $currentVendor = $this->getCurrentVendor($invoice ? $invoice->getStoreId() : null);

        return $currentVendor->sendResellRequest($request, $invoice);
    }

    /**
     * @inheritDoc
     */
    public function buildRequest(
        $salesEntity,
        $paymentMethod = null,
        $shippingPaymentObject = null,
        array $receiptData = [],
        $clientName = '',
        $clientInn = ''
    ): RequestInterface {
        $currentVendor = $this->getCurrentVendor($salesEntity->getStoreId());

        return $currentVendor->buildRequest(
            $salesEntity,
            $paymentMethod,
            $shippingPaymentObject,
            $receiptData,
            $clientName,
            $clientInn
        );
    }

    /**
     * @inheritDoc
     */
    public function buildRequestForResellRefund($invoice): RequestInterface
    {
        $currentVendor = $this->getCurrentVendor($invoice->getStoreId());

        return $currentVendor->buildRequestForResellRefund($invoice);
    }

    /**
     * @inheritDoc
     */
    public function buildRequestForResellSell($invoice): RequestInterface
    {
        $currentVendor = $this->getCurrentVendor($invoice->getStoreId());

        return $currentVendor->buildRequestForResellSell($invoice);
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function updateStatus($uuid, $useAttempt = false)
    {
        $entity = $this->requestHelper->getEntityByUuid($uuid);
        $currentVendor = $this->getCurrentVendor($entity->getStoreId());

        if (!$currentVendor instanceof StatusUpdatable) {
            throw new \Exception(__('Not implemented'));
        }

        return $currentVendor->updateStatus($uuid, $useAttempt);
    }

    /**
     * @param ResponseInterface $response
     * @param int|string|null $storeId
     * @throws \Exception
     * @throws LocalizedException
     * @return CreditmemoInterface|InvoiceInterface
     */
    public function saveCallback($response, $storeId = null)
    {
        $currentVendor = $this->getCurrentVendor($storeId);

        if (!$currentVendor instanceof \Mygento\Kkm\Model\Atol\Vendor) {
            throw new \Exception(__('Not implemented'));
        }

        return $currentVendor->saveCallback($response);
    }

    /**
     * @param int|string|null $storeId
     * @throws InvalidArgumentException
     * @return VendorInterface
     */
    private function getCurrentVendor($storeId = null): VendorInterface
    {
        $currentVendorCode = $this->helper->getCurrentVendorCode($storeId);

        if (!isset($this->vendors[$currentVendorCode])) {
            throw new InvalidArgumentException(__('No such Kkm vendor: %1', $currentVendorCode));
        }

        return $this->vendors[$currentVendorCode];
    }
}
