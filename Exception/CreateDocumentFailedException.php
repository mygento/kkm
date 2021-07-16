<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Exception;

use Mygento\Kkm\Api\Data\ResponseInterface;

class CreateDocumentFailedException extends ResponseValidationException
{
    /**
     * @var array
     */
    private $debugData = [];

    /**
     * @param string $message
     * @param ResponseInterface|null $response
     * @param array $debugData
     */
    public function __construct($message, ResponseInterface $response = null, $debugData = [])
    {
        parent::__construct($message, $response);

        $this->response = $response;
        $this->debugData = $debugData;
    }

    /**
     * @return array
     */
    public function getDebugData(): array
    {
        return $this->debugData;
    }
}
