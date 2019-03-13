<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

use Mygento\Kkm\Api\Data\ResponseInterface;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Exception\CreateDocumentFailedException;
use Mygento\Kkm\Exception\VendorBadServerAnswerException;
use Mygento\Kkm\Model\Source\ApiVersion;

class Client
{
    const REQUEST_URL = 'https://online.atol.ru/possystem/v%u/';
    const REQUEST_TEST_URL = 'https://testonline.atol.ru/possystem/v%u/';

    //see Atol Documentation
    const ALLOWED_HTTP_STATUSES = [200, 400, 401];

    const GET_TOKEN_URL_APPNX   = 'getToken';
    const SELL_URL_APPNX        = 'sell';
    const SELL_REFUND_URL_APPNX = 'sell_refund';
    const REPORT_URL_APPNX      = 'report';

    protected $apiVersion = ApiVersion::API_VERSION_4;

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $kkmHelper;

    /**
     * @var \Magento\Framework\HTTP\Client\CurlFactory
     */
    private $curlClientFactory;

    /**
     * @var string
     */
    private $token;
    /**
     * @var \Mygento\Kkm\Model\Atol\ResponseFactory
     */
    private $responseFactory;

    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Model\Atol\ResponseFactory $responseFactory,
        \Magento\Framework\HTTP\Client\CurlFactory $curlFactory
    ) {
        $this->kkmHelper         = $kkmHelper;
        $this->responseFactory   = $responseFactory;
        $this->curlClientFactory = $curlFactory;
    }

    /**
     * @throws \Exception
     * @return string
     */
    public function getToken(): string
    {
        if ($this->token) {
            return $this->token;
        }
        $helper   = $this->kkmHelper;
        $login    = $helper->getConfig('atol/login');
        $password = $helper->decrypt($helper->getConfig('atol/password'));

        $dataBody = json_encode(
            [
                'login' => $login,
                'pass'  => $password,
            ]
        );

        $url = $this->getBaseUrl() . self::GET_TOKEN_URL_APPNX;

        $curl = $this->curlClientFactory->create();
        $curl->addHeader('Content-Type', 'application/json; charset=utf-8');
        $curl->post($url, $dataBody);
        $response = $curl->getBody();

        $decodedResult = json_decode($response);

        if (!$decodedResult || !isset($decodedResult->token) || $decodedResult->token == '') {
            throw new \Exception(
                __('Response from Atol does not contain valid token value. Response: ')
                . strval($response)
            );
        }

        $this->token = $decodedResult->token;

        $this->kkmHelper->info('Token: ' . $this->token);

        return $this->token;
    }

    /**
     * @param string $uuid
     * @return ResponseInterface
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @throws \Exception
     */
    public function receiveStatus(string $uuid): ResponseInterface
    {
        $this->kkmHelper->info("START updating status for uuid {$uuid}");

        $groupCode = $this->getGroupCode();
        $url       = $this->getBaseUrl() . $groupCode . '/' . self::REPORT_URL_APPNX . '/' . $uuid;
        $this->kkmHelper->debug('URL: ' . $url);

        $responseRaw = $this->sendGetRequest($url);
        $response    = $this->responseFactory->create(['jsonRaw' => $responseRaw]);

        $this->kkmHelper->info('New status: ' . $response->getStatus());
        $this->kkmHelper->debug('Response: ' . $response);

        return $response;
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     */
    public function sendRefund($request): ResponseInterface
    {
        $debugData = [];
        $this->kkmHelper->info('START Sending creditmemo');
        $this->kkmHelper->debug('Request', $request->jsonSerialize());

        $request = $debugData['request'] = json_encode($request);

        try {
            $groupCode = $this->getGroupCode();
            $url  = $this->getBaseUrl() . $groupCode . '/' . self::SELL_REFUND_URL_APPNX;
            $debugData['url'] = $url;
            $this->kkmHelper->debug('URL: ' . $url);

            $responseRaw = $this->sendPostRequest($url, $request);
            $response = $this->responseFactory->create(['jsonRaw' => $responseRaw]);

            $this->kkmHelper->info(__('Creditmemo is sent. Uuid: %1', $response->getUuid()));
            $this->kkmHelper->debug('Response:', [$response]);
        } catch (VendorBadServerAnswerException $exc) {
            throw $exc;
        } catch (\Exception $exc) {
            throw new CreateDocumentFailedException(
                $exc->getMessage(),
                $response ?? null,
                $debugData
            );
        }

        return $response;
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     */
    public function sendSell($request): ResponseInterface
    {
        $debugData = [];
        $this->kkmHelper->info('START Sending invoice');
        $this->kkmHelper->debug('Request:', $request->jsonSerialize());

        $request = $debugData['request'] = json_encode($request);

        try {
            $groupCode = $this->getGroupCode();
            $url = $this->getBaseUrl() . $groupCode . '/' . self::SELL_URL_APPNX;
            $debugData['url'] = $url;
            $this->kkmHelper->debug('URL: ' . $url);

            $responseRaw = $this->sendPostRequest($url, $request);
            $response = $this->responseFactory->create(['jsonRaw' => $responseRaw]);

            $this->kkmHelper->info(__('Invoice is sent. Uuid: %1', $response->getUuid()));
            $this->kkmHelper->debug('Response:', [$response]);
        } catch (VendorBadServerAnswerException $exc) {
            throw $exc;
        } catch (\Exception $exc) {
            throw new CreateDocumentFailedException(
                $exc->getMessage(),
                $response ?? null,
                $debugData
            );
        }

        return $response;
    }

    protected function getBaseUrl()
    {
        $url = $this->kkmHelper->isTestMode()
            ? self::REQUEST_TEST_URL
            : self::REQUEST_URL;

        return sprintf($url, $this->getApiVersion());
    }

    /**
     * @return int
     */
    public function getApiVersion()
    {
        $apiVersion = $this->kkmHelper->getConfig('atol/api_version');
        $this->apiVersion = in_array($apiVersion, ApiVersion::getAllVersions())
            ? $apiVersion
            : ApiVersion::API_VERSION_4;

        return $this->apiVersion;
    }

    /**
     * @param $url
     * @param array|string $params - use $params as a string in case of JSON POST request.
     * @return string
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     */
    protected function sendPostRequest($url, $params = []): string
    {
        try {
            $curl = $this->curlClientFactory->create();
            $curl->addHeader('Content-Type', 'application/json; charset=utf-8');
            $curl->addHeader('Token', $this->getToken());
            $curl->post($url, $params);
            $response = $curl->getBody();
        } catch (\Exception $e) {
            throw new VendorBadServerAnswerException('No response from Atol. ' . $url);
        }

        if (!in_array($curl->getStatus(), self::ALLOWED_HTTP_STATUSES)) {
            throw new VendorBadServerAnswerException(
                'Bad response from Atol. Status: ' . $curl->getStatus()
                . ($response ? '. Response: ' . (string)$response : '')
            );
        }

        if (!$curl->getBody()) {
            throw new VendorBadServerAnswerException('Empty response from Atol.');
        }

        return $response;
    }

    /**
     * @param $url
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @return string
     */
    protected function sendGetRequest($url): string
    {
        try {
            $curl = $this->curlClientFactory->create();
            $curl->addHeader('Token', $this->getToken());
            $curl->get($url);
            $response = $curl->getBody();
        } catch (\Exception $e) {
            throw new VendorBadServerAnswerException('No response from Atol.');
        }

        if (!in_array($curl->getStatus(), self::ALLOWED_HTTP_STATUSES)) {
            throw new VendorBadServerAnswerException(
                'Bad response from Atol. Status: ' . $curl->getStatus()
                . ($response ? '. Response: ' . (string)$response : '')
            );
        }

        if (!$curl->getBody()) {
            throw new VendorBadServerAnswerException('Empty response from Atol.');
        }

        return $response;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getGroupCode()
    {
        $groupCode = $this->kkmHelper->getConfig('atol/group_code');
        if (!$groupCode) {
            throw new \Exception(
                'No groupCode. Please set up the module properly.'
            );
        }

        return $groupCode;
    }
}
