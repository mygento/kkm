<?php

namespace Mygento\Kkm\Ui\Component\Listing\Column\ChequeStatus;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Mygento\Kkm\Model\Source\SalesEntityType;

class Actions extends Column
{
    private const URL_ORDER_VIEW = 'sales/order/view';
    private const URL_INVOICE_VIEW = 'sales/invoice/view';
    private const URL_CREDITMEMO_VIEW = 'sales/creditmemo/view';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);

        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['entity_id'])) {
                continue;
            }

            $actions = [
                'showOrder' => [
                    'href' => $this->urlBuilder->getUrl(
                        self::URL_ORDER_VIEW,
                        [
                            'order_id' => $item['order_id']
                        ]
                    ),
                    'label' => __('Show Order')
                ],
                'showEntity' => [
                    'href' => $this->getEntityUrl($item),
                    'label' => $this->getEntityActionLabel($item)
                ],
            ];

            if (!$item['is_closed']) {
                $actions['resend'] = [
                    'href' => $this->urlBuilder->getUrl(
                        'kkm/cheque/resend',
                        [
                            'entity' => $item['sales_entity_type'],
                            'id' => $item['sales_entity_id'],
                            'store_id' => $item['store_id'],
                        ]
                    ),
                    'label' => __('Resend')
                ];
            }

            $item[$this->getData('name')] = $actions;
        }

        return $dataSource;
    }

    /**
     * @param $item
     * @return string
     */
    private function getEntityUrl($item)
    {
        return $item['sales_entity_type'] == SalesEntityType::ENTITY_TYPE_INVOICE
            ? $this->urlBuilder->getUrl(
            self::URL_INVOICE_VIEW,
            [
                'invoice_id' => $item['sales_entity_id']
            ]
        ): $this->urlBuilder->getUrl(
            self::URL_CREDITMEMO_VIEW,
            [
                'creditmemo_id' => $item['sales_entity_id']
            ]
        );
    }

    /**
     * @param $item
     * @return \Magento\Framework\Phrase
     */
    private function getEntityActionLabel($item)
    {
        return $item['sales_entity_type'] == SalesEntityType::ENTITY_TYPE_INVOICE
            ? __('Show Invoice')
            : __('Show Creditmemo');
    }
}