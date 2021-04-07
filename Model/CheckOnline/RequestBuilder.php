<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\CheckOnline;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\EntityInterface;
use Mygento\Base\Helper\Discount;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Helper\Data;
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

    public function __construct(
        Data $helper,
        RequestFactory $requestFactory,
        GetRecalculated $getRecalculated,
        ItemFactory $itemFactory
    ) {
        $this->helper = $helper;
        $this->requestFactory = $requestFactory;
        $this->getRecalculated = $getRecalculated;
        $this->itemFactory = $itemFactory;
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
                $request->setOperationType(Request::CHECKONLINE_SELL_OPERATION_TYPE);
                break;
            case 'creditmemo':
                $request->setOperationType(Request::REFUND_OPERATION_TYPE);
                break;
        }

        $storeId = $salesEntity->getStoreId();
        $order = $salesEntity->getOrder() ?? $salesEntity;
        $telephone = $order->getBillingAddress()
            ? (string) $order->getBillingAddress()->getTelephone()
            : '';

        $request
            ->setEntityStoreId($storeId)
            ->setSalesEntityId($salesEntity->getEntityId())
            ->setClientId($this->helper->getConfig('checkonline/client_id', $storeId))
            ->setExternalId($this->generateExternalId($salesEntity))
            ->setNonCash(array(round($order->getGrandTotal() * 100, 0)))
            ->setSno((int) $this->helper->getConfig('checkonline/sno', $storeId))
            ->setPhone($telephone)
            ->setEmail($order->getCustomerEmail())
            ->setPlace($order->getStore()->getBaseUrl())
            ->setItems($this->buildItems($salesEntity))
        ;

        return $request;
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
     * @param CreditmemoInterface|InvoiceInterface|OrderInterface $salesEntity
     * @param string|null $storeId
     * @return array
     */
    private function buildItems($salesEntity, $storeId = null)
    {
        $items = [];
        $recalculatedItems = $this->getRecalculated->execute($salesEntity);

        foreach ($recalculatedItems[Discount::ITEMS] as $key => $itemData) {
            //todo is it need?
            //For orders without Shipping (Virtual products)
//            if ($key == Discount::SHIPPING && $itemData[Discount::NAME] === null) {
//                continue;
//            }
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
