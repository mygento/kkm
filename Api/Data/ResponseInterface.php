<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api\Data;

interface ResponseInterface
{
    /**
     * @return string
     */
    public function getUuid();

    /**
     * @return null|string
     */
    public function getErrorMessage();

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @return string
     */
    public function getStatus();

    /**
     * @return string
     */
    public function getTimestamp();

    /**
     * @return bool
     */
    public function isDone();

    /**
     * @return bool
     */
    public function isFailed();

    /**
     * @return bool
     */
    public function isWait();

    /**
     * @return string
     */
    public function __toString();
}
