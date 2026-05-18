<?php

/**
 * @author Mygento Team
 * @copyright 2017-2026 Mygento (https://www.mygento.com)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

use Magento\Framework\Exception\LocalizedException;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Helper\Data;

class RequestFactory
{
    /**
     * @var Data
     */
    private $kkmHelper;

    /**
     * @var array
     */
    private $requestFactories;

    /**
     * RequestFactory constructor.
     * @param Data $kkmHelper
     * @param array $requestFactories
     */
    public function __construct(
        Data $kkmHelper,
        array $requestFactories = [],
    ) {
        $this->kkmHelper = $kkmHelper;
        $this->requestFactories = $requestFactories;
    }

    /**
     * Create class instance
     *
     * @param string|null
     * @param mixed|null $storeId
     * @return RequestInterface
     */
    public function create($storeId = null)
    {
        $version = $this->kkmHelper->getConfig('atol/api_version', $storeId);

        if (!isset($this->requestFactories[$version])) {
            throw new \InvalidArgumentException("Invalid version {$version}");
        }

        $requestFactory = $this->requestFactories[$version];
        if (!is_object($requestFactory) || !method_exists($requestFactory, 'create')) {
            throw new LocalizedException(__('Invalid request factory object provided.'));
        }

        return $requestFactory->create();
    }
}
