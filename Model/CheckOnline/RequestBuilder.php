<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\CheckOnline;

use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\EntityInterface;
use Mygento\Base\Helper\Discount;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Helper\Data;
use Mygento\Kkm\Helper\Request as RequestHelper;
use Mygento\Kkm\Model\CheckOnline\RequestFactory;
use Mygento\Kkm\Model\GetRecalculated;
use Mygento\Kkm\Model\CheckOnline\Item;
use Mygento\Kkm\Model\CheckOnline\ItemFactory;

class RequestBuilder
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var GetRecalculated
     */
    private $getRecalculated;

    /**
     * @var ItemFactory
     */
    private $itemFactory;

    /**
     * @var RequestHelper
     */
    private $requestHelper;

    public function __construct(
        Data $helper,
        RequestFactory $requestFactory,
        GetRecalculated $getRecalculated,
        ItemFactory $itemFactory,
        RequestHelper $requestHelper
    ) {
        $this->helper = $helper;
        $this->requestFactory = $requestFactory;
        $this->getRecalculated = $getRecalculated;
        $this->itemFactory = $itemFactory;
        $this->requestHelper = $requestHelper;
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface|OrderInterface $salesEntity
     * @return RequestInterface
     */
    public function buildRequest($salesEntity): RequestInterface
    {
        /** @var \Mygento\Kkm\Model\CheckOnline\Request $request */
        $request = $this->requestFactory->create();

        switch ($salesEntity->getEntityType()) {
            case 'invoice':
                $request->setOperationType(Request::SELL_OPERATION_TYPE);
                $request->setEntityType('Invoice');
                break;
            case 'creditmemo':
                $request->setOperationType(Request::REFUND_OPERATION_TYPE);
                $request->setEntityType('Refund');
                break;
        }

        $order = $salesEntity->getOrder() ?? $salesEntity;
        $storeId = $order->getStoreId();
        $telephone = $order->getBillingAddress()
            ? (string) $order->getBillingAddress()->getTelephone()
            : '';

        $request
            ->setEntityStoreId($storeId)
            ->setSalesEntityId($salesEntity->getEntityId())
            ->setClientId($this->helper->getConfig('checkonline/client_id', $storeId))
            ->setGroup($this->helper->getConfig('checkonline/group', $storeId))
            ->setExternalId($this->requestHelper->generateExternalId($salesEntity))
            ->setNonCash(array((int) round($order->getGrandTotal() * 100, 0)))
            ->setSno((int) $this->helper->getConfig('checkonline/sno', $storeId))
            ->setPhone($telephone)
            ->setEmail($order->getCustomerEmail())
            ->setPlace($order->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK, true))
            ->setItems($this->buildItems($salesEntity))
            ->setFullResponse((bool) $this->helper->getConfig('checkonline/full_response', $storeId))
        ;

        return $request;
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface|OrderInterface $salesEntity
     * @param string|null $storeId
     * @return array
     */
    private function buildItems($salesEntity, $storeId = null)
    {
        $items = [];
        $recalculatedItems = $this->getRecalculated->execute($salesEntity);

        foreach ($recalculatedItems[Discount::ITEMS] as $key => $itemData) {
            // For orders without Shipping (Virtual products)
            if ($key == Discount::SHIPPING && $itemData[Discount::NAME] === null) {
                continue;
            }

            //todo validation
//            $this->validateItem($itemData);

            //todo process giftcard
            $itemPaymentMethod = Item::PAYMENT_METHOD_FULL_PAYMENT;
            $itemPaymentObject = $key == Discount::SHIPPING ? Item::PAYMENT_OBJECT_SERVICE : Item::PAYMENT_OBJECT_BASIC;
            $itemQty = $itemData[Discount::QUANTITY] ?? 1;

            //todo process marking
            /** @var Item $item */
            $item = $this->itemFactory->create();
            $item
                ->setName($itemData[Discount::NAME])
                ->setPrice(round($itemData[Discount::PRICE] * 100, 0))
                ->setQuantity($itemQty * 1000)
                ->setTax(Item::TAX_MAPPING[$itemData[Discount::TAX]])
                ->setPaymentMethod($itemPaymentMethod)
                ->setPaymentObject($itemPaymentObject)
            ;

            $items[] = $item;
        }

        return $items;
    }
}
