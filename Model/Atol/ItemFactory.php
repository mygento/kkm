<?php

/**
 * @author Mygento Team
 * @copyright 2017-2026 Mygento (https://www.mygento.com)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

use Magento\Framework\Exception\LocalizedException;
use Mygento\Kkm\Helper\Data;
use Mygento\Kkm\Model\Request\Item;

class ItemFactory
{
    /**
     * @var Data
     */
    private $kkmHelper;

    /**
     * @var array
     */
    private $itemFactories;

    /**
     * @param Data $kkmHelper
     * @param array $itemFactories
     */
    public function __construct(
        Data $kkmHelper,
        array $itemFactories = []
    ) {
        $this->kkmHelper = $kkmHelper;
        $this->itemFactories = $itemFactories;
    }

    /**
     * @param $storeId
     * @return Item
     * @throws LocalizedException
     */
    public function create($storeId)
    {
        $version = $this->kkmHelper->getConfig('atol/api_version', $storeId);

        if (!isset($this->itemFactories[$version])) {
            throw new \InvalidArgumentException("Invalid version $version");
        }

        $itemFactory = $this->itemFactories[$version];
        if (!is_object($itemFactory) || !method_exists($itemFactory, 'create')) {
            throw new LocalizedException(__('Invalid item factory object provided.'));
        }

        return $itemFactory->create();
    }
}
