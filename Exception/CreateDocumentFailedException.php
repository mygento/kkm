<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Exception;

use Mygento\Kkm\Api\Data\ResponseInterface;

class CreateDocumentFailedException extends \Exception
{
    /**
     * @var array
     */
    private $debugData = [];

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @param string $message
     * @param ResponseInterface|null $response
     * @param array $debugData
     */
    public function __construct($message, ResponseInterface $response = null, $debugData = [])
    {
        $this->debugData = $debugData;
        $this->response = $response;
        parent::__construct($message, 0, null);
    }

    /**
     * @return ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return array
     */
    public function getDebugData(): array
    {
        return $this->debugData;
    }
}
