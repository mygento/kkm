<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Exception;

use Mygento\Kkm\Api\Data\ResponseInterface;

class ResponseValidationException extends \Exception
{
    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @param string $message
     * @param ResponseInterface|null $response
     */
    public function __construct($message = '', ResponseInterface $response = null)
    {
        parent::__construct($message, 0, null);

        $this->response = $response;
    }

    /**
     * @return ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
    }
}
