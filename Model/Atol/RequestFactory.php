<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

/**
 * Class RequestFactory
 * allows to support several formats of ATOL requests.
 * In order to add new format - create class extending abstract class Request
 * and add dependency to this class
 *
 * @package Mygento\Kkm\Model\Atol
 */
class RequestFactory
{
    /**
     * @var \Mygento\Kkm\Model\Atol\RequestForVersion3Factory
     * @deprecated
     */
    private $request3Factory;

    /**
     * @var \Mygento\Kkm\Model\Atol\RequestForVersion4Factory
     */
    private $request4Factory;

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $kkmHelper;

    /**
     * RequestFactory constructor.
     * @param \Mygento\Kkm\Model\Atol\RequestForVersion3Factory $request3Factory
     * @param \Mygento\Kkm\Model\Atol\RequestForVersion4Factory $request4Factory
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     */
    public function __construct(
        \Mygento\Kkm\Model\Atol\RequestForVersion3Factory $request3Factory,
        \Mygento\Kkm\Model\Atol\RequestForVersion4Factory $request4Factory,
        \Mygento\Kkm\Helper\Data $kkmHelper
    ) {
        $this->request3Factory = $request3Factory;
        $this->request4Factory = $request4Factory;
        $this->kkmHelper = $kkmHelper;
    }

    /**
     * Create class instance
     *
     * @param string|null
     * @param mixed|null $storeId
     * @return \Mygento\Kkm\Api\Data\RequestInterface
     */
    public function create($storeId = null)
    {
        $version = $this->kkmHelper->getConfig('atol/api_version', $storeId);

        return $version == 3
            ? $this->request3Factory->create()
            : $this->request4Factory->create();
    }
}
