<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Api\Processor;

use Mygento\Kkm\Api\Data\ResponseInterface;
use Mygento\Kkm\Api\Data\UpdateRequestInterface;

interface UpdateInterface
{
    public const TOPIC_NAME_UPDATE = 'mygento.kkm.message.update';

    /**
     * @param string $uuid
     * @return ResponseInterface
     */
    public function proceedSync(string $uuid): ResponseInterface;

    /**
     * @param \Mygento\Kkm\Api\Data\UpdateRequestInterface $updateRequest
     * @throws \InvalidArgumentException
     * @return bool
     */
    public function proceedAsync(UpdateRequestInterface $updateRequest): bool;

    /**
     * @param string $uuid
     * @return ResponseInterface
     */
    public function proceedUsingAttempt(string $uuid): ResponseInterface;
}
