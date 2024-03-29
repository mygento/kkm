<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Ui\Component\Listing\Columns;

use Magento\Ui\Component\Listing\Columns\Column;

class OrderIdLink extends Column
{
    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');

            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[$fieldName])) {
                    $item[$fieldName] = sprintf(
                        "<a href='%s'>%s</a>",
                        $this->context->getUrl('sales/order/view', ['order_id' => $item[$fieldName]]),
                        $item[$fieldName]
                    );
                }
            }
        }

        return $dataSource;
    }
}
