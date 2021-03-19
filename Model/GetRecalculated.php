<?php

/**
 * @author Mygento Team
 * @copyright 2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\OrderItemInterfaceFactory;
use Mygento\Base\Helper\Discount;
use Mygento\Kkm\Helper\Data;

class GetRecalculated
{
    /**
     * @var \Mygento\Base\Helper\Discount
     */
    private $discountHelper;
    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $configHelper;
    /**
     * @var \Magento\Sales\Api\Data\OrderInterfaceFactory
     */
    private $orderFactory;
    /**
     * @var \Magento\Sales\Api\Data\OrderItemInterfaceFactory
     */
    private $orderItemFactory;
    /**
     * @var \Mygento\Base\Service\RecalculatorFacade
     */
    private $recalculatorFacade;

    public function __construct(
        Discount $kkmDiscount,
        Data $kkmHelper,
        OrderInterfaceFactory $orderFactory,
        OrderItemInterfaceFactory $orderItemFactory,

        //TODO: Может его заюзать, а?!
        \Mygento\Base\Service\RecalculatorFacade $recalculatorFacade
    ) {
        $this->discountHelper = $kkmDiscount;
        $this->configHelper = $kkmHelper;
        $this->orderFactory = $orderFactory;
        $this->orderItemFactory = $orderItemFactory;
        $this->recalculatorFacade = $recalculatorFacade;
    }

    /**
     * @param CreditmemoInterface|InvoiceInterface $salesEntity
     * @throws \Exception
     * @return array|null
     */
    public function execute($salesEntity)
    {
        $orderMock = $this->getMockOrder($salesEntity);

        //Особенность отправки чеков в ККМ: цены продуктов должны быть оригинальными
        //а GiftCard и StoreCredit должны быть отправлены как аванс
        $orderMock->setGrandTotal(round(
            $orderMock->getGrandTotal()
            //Magento Commerce Features
            + $orderMock->getData('gift_cards_amount')
            + $orderMock->getData('customer_balance_amount'),
            4
        ));

        $storeId = $salesEntity->getStoreId();
        $shippingTax = $this->configHelper->getConfig('general/shipping_tax', $storeId);
        $taxValue = $this->configHelper->getConfig('general/tax_options', $storeId);
        $attributeCode = '';
        if (!$this->configHelper->getConfig('general/tax_all', $storeId)) {
            $attributeCode = $this->configHelper->getConfig('general/product_tax_attr', $storeId);
        }

        if (!$this->configHelper->getConfig('general/default_shipping_name', $storeId)) {
            $shippingDescription = $this->configHelper->getConfig('general/custom_shipping_name', $storeId);
            $orderMock->setShippingDescription($shippingDescription);
        }

        $this->configureDiscountHelper();
        $markingAttribute = '';
        $markingListAttribute = '';
        $markingRefundAttribute = '';

        if ($this->configHelper->isMarkingEnabled($storeId)) {
            $markingAttribute = $this->configHelper->getMarkingShouldField($storeId);
            $markingListAttribute = $this->configHelper->getMarkingField($storeId);
            $markingRefundAttribute = $this->configHelper->getMarkingRefundField($storeId);
        }

        return $this->discountHelper->getRecalculated(
            $orderMock,
                $taxValue,
                $attributeCode,
                $shippingTax,
                $markingAttribute,
                $markingListAttribute,
                $markingRefundAttribute
            );
    }

    /**
     * Чтобы не повлиять на данные оригинальной сущности
     * возвращает заказ (OrderInterface),
     * полностью дублирующий сущность (CreditmemoInterface или InvoiceInterface)
     *
     * @param CreditmemoInterface|InvoiceInterface $salesEntity
     * @return OrderInterface
     */
    public function getMockOrder($salesEntity): OrderInterface
    {
        $orderMock = $this->orderFactory->create(['data' => $salesEntity->getData()]);

        $items = array_map(
            function ($item) {
                $itemMock = $this->orderItemFactory->create(['data' => $item->getData()]);

                return $itemMock->setId($item->getId());
            },
            $salesEntity->getItems()
        );

        return $orderMock->setItems($items);
    }

    /**
     * Set mode flags for Discount logic
     * @param int|null $storeId
     */
    protected function configureDiscountHelper($storeId = null)
    {
        $applyAlgo = $this->configHelper->getConfig('recalculating/apply_algorithm', $storeId);
        $this->kkmDiscount->setDoCalculation((bool) $applyAlgo);
        if ($applyAlgo) {
            $isSpreadAllowed = $this->configHelper->getConfig('general/spread_discount', $storeId);
            $isSplitAllowed = $this->configHelper->getConfig('general/split_allowed', $storeId);

            $this->kkmDiscount->setSpreadDiscOnAllUnits((bool) $isSpreadAllowed);
            $this->kkmDiscount->setIsSplitItemsAllowed((bool) $isSplitAllowed);
        }
    }

}
