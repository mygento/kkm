<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Config\Backend;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class CertFile extends \Mygento\Base\Model\Config\Backend\File
{
    /**
     * Upload max file size in kilobytes
     *
     * @var int
     */
    protected $maxFileSize = 500;

    /**
     * Getter for allowed extensions of uploaded files
     *
     * @return array
     */
    protected function getAllowedExtensions()
    {
        return ['pem', 'crt', 'key', 'cer', 'der', 'p7b', 'p7c'];
    }
}
