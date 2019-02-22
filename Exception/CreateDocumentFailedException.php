<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Exception;

use Mygento\Kkm\Model\Atol\Response;

class CreateDocumentFailedException extends \Exception
{
    private $debugData = [];
    /**
     * @var \Mygento\Kkm\Model\Atol\Response
     */
    private $response;

    public function __construct($message, Response $response = null, $debugData = [])
    {
        $this->debugData = $debugData;
        $this->response  = $response;
        parent::__construct($message, 0, null);
    }

    /**
     * @return null|Response
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
