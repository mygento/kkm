<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Exception;

class AuthorizationException extends \Exception
{
    /**
     * @var string|null
     */
    private $errorCode;

    /**
     * @var string|null
     */
    private $errorType;

    /**
     * @param string $message
     * @param string $errorCode
     * @param string $errorType
     */
    public function __construct(
        $message,
        $errorCode = null,
        $errorType = null
    ) {
        parent::__construct($message);

        $this->errorCode = $errorCode;
        $this->errorType = $errorType;
    }

    /**
     * @return string|null
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * @return string|null
     */
    public function getErrorType(): ?string
    {
        return $this->errorType;
    }
}
