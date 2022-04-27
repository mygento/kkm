<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Atol;

use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\ResponseInterface;
use Mygento\Kkm\Exception\AuthorizationException;
use Mygento\Kkm\Exception\CreateDocumentFailedException;
use Mygento\Kkm\Exception\VendorBadServerAnswerException;
use Mygento\Kkm\Model\Source\ApiVersion;

class Client
{
    private const REQUEST_URL = 'https://online.atol.ru/possystem/v%u/';
    private const REQUEST_TEST_URL = 'https://testonline.atol.ru/possystem/v%u/';

    //see Atol Documentation
    private const ALLOWED_HTTP_STATUSES = [100, 200, 400, 401];
    private const GET_TOKEN_URL_APPNX = 'getToken';
    private const SELL_URL_APPNX = 'sell';
    private const SELL_REFUND_URL_APPNX = 'sell_refund';
    private const REPORT_URL_APPNX = 'report';

    /**
     * @var int
     */
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
     * @var \Mygento\Kkm\Model\Atol\ResponseFactory
     */
    private $responseFactory;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * Client constructor.
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param ResponseFactory $responseFactory
     * @param \Magento\Framework\HTTP\Client\CurlFactory $curlFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     */
    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Model\Atol\ResponseFactory $responseFactory,
        \Magento\Framework\HTTP\Client\CurlFactory $curlFactory,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
    ) {
        $this->kkmHelper = $kkmHelper;
        $this->responseFactory = $responseFactory;
        $this->curlClientFactory = $curlFactory;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @param int|string|null $storeId
     * @throws \Exception
     * @return string
     */
    public function getToken($storeId = null): string
    {
        $helper = $this->kkmHelper;
        $login = $helper->getAtolLogin($storeId);
        $password = $helper->decrypt($helper->getAtolPassword($storeId));

        $dataBody = $this->jsonSerializer->serialize(
            [
                'login' => $login,
                'pass' => $password,
            ]
        );

        $url = $this->getBaseUrl($storeId) . self::GET_TOKEN_URL_APPNX;

        $curl = $this->curlClientFactory->create();
        $curl->addHeader('Content-Type', 'application/json; charset=utf-8');
        $curl->post($url, $dataBody);
        $response = $curl->getBody();

        $decodedResult = $this->jsonSerializer->unserialize($response);

        if (!$decodedResult || !isset($decodedResult['token']) || $decodedResult['token'] == '') {
            throw new AuthorizationException(
                __('Response from Atol does not contain valid token value. Response: ') . (string) $response,
                $decodedResult['error']['code'] ?? null,
                $decodedResult['error']['type'] ?? null
            );
        }

        $token = $decodedResult['token'];

        $this->kkmHelper->info('Token: ' . $token);

        return $token;
    }

    /**
     * @param string $uuid
     * @param int|string|null $storeId
     * @throws \Exception
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @return ResponseInterface
     */
    public function receiveStatus(string $uuid, $storeId = null): ResponseInterface
    {
        $this->kkmHelper->info("START updating status for uuid {$uuid}");

        $token = $this->getToken($storeId);
        $groupCode = $this->getGroupCode($storeId);
        $url = $this->getBaseUrl($storeId) . $groupCode . '/' . self::REPORT_URL_APPNX . '/' . $uuid;
        $this->kkmHelper->debug('URL: ' . $url);

        $responseRaw = $this->sendGetRequest($url, $token);
        $response = $this->responseFactory->create(['jsonRaw' => $responseRaw]);

        $this->kkmHelper->info('New status: ' . $response->getStatus());
        $this->kkmHelper->debug('Response: ' . $response);

        return $response;
    }

    /**
     * @param RequestInterface $request
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @throws AuthorizationException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @return ResponseInterface
     */
    public function sendRefund($request): ResponseInterface
    {
        $debugData = [];
        $this->kkmHelper->info('START Sending refund');
        $this->kkmHelper->debug('Request', $request->__toArray());

        $storeId = $request->getStoreId();
        $request = $debugData['request'] = $this->jsonSerializer->serialize($request);

        try {
            $token = $this->getToken($storeId);
            $groupCode = $this->getGroupCode($storeId);
            $url = $this->getBaseUrl($storeId) . $groupCode . '/' . self::SELL_REFUND_URL_APPNX;
            $debugData['url'] = $url;
            $this->kkmHelper->debug('URL: ' . $url);

            $responseRaw = $this->sendPostRequest($url, $token, $request);
            $response = $this->responseFactory->create(['jsonRaw' => $responseRaw]);

            $this->kkmHelper->info(__('Refund is sent. Uuid: %1', $response->getIdForTransaction()));
            $this->kkmHelper->debug('Response:', [$response]);
        } catch (AuthorizationException | VendorBadServerAnswerException $exc) {
            throw $exc;
        } catch (\Throwable $exc) {
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
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @throws AuthorizationException
     * @throws \Mygento\Kkm\Exception\CreateDocumentFailedException
     * @return ResponseInterface
     */
    public function sendSell($request): ResponseInterface
    {
        $debugData = [];
        $this->kkmHelper->info('START Sending invoice');
        $this->kkmHelper->debug('Request:', $request->__toArray());

        $storeId = $request->getStoreId();

        $request = $debugData['request'] = $this->jsonSerializer->serialize($request);
        $response = null;

        try {
            $token = $this->getToken($storeId);
            $groupCode = $this->getGroupCode($storeId);
            $url = $this->getBaseUrl($storeId) . $groupCode . '/' . self::SELL_URL_APPNX;
            $debugData['url'] = $url;
            $this->kkmHelper->debug('URL: ' . $url);

            $responseRaw = $this->sendPostRequest($url, $token, $request);
            $response = $this->responseFactory->create(['jsonRaw' => $responseRaw]);

            $this->kkmHelper->info(__('Invoice is sent. Uuid: %1', $response->getIdForTransaction()));
            $this->kkmHelper->debug('Response:', [$response]);
        } catch (AuthorizationException | VendorBadServerAnswerException $exc) {
            throw $exc;
        } catch (\Exception $exc) {
            throw new CreateDocumentFailedException(
                $exc->getMessage(),
                $response,
                $debugData
            );
        }

        return $response;
    }

    /**
     * @param int|string|null $storeId
     * @return int
     */
    public function getApiVersion($storeId = null)
    {
        $apiVersion = $this->kkmHelper->getConfig('atol/api_version', $storeId);
        $this->apiVersion = in_array($apiVersion, ApiVersion::getAllVersions())
            ? $apiVersion
            : ApiVersion::API_VERSION_4;

        return $this->apiVersion;
    }

    /**
     * Returns Atol Url depends on is test mode on/off
     * @param int|string|null $storeId
     * @return string
     */
    protected function getBaseUrl($storeId = null): string
    {
        $url = $this->kkmHelper->isTestMode($storeId)
            ? self::REQUEST_TEST_URL
            : self::REQUEST_URL;

        return sprintf($url, $this->getApiVersion($storeId));
    }

    /**
     * @param string $url
     * @param string $token
     * @param array|string $params - use $params as a string in case of JSON POST request.
     * @throws AuthorizationException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @return string
     */
    protected function sendPostRequest(string $url, string $token, $params = []): string
    {
        try {
            $curl = $this->curlClientFactory->create();
            $curl->addHeader('Content-Type', 'application/json; charset=utf-8');
            $curl->addHeader('Token', $token);
            $curl->post($url, $params);
            $response = $curl->getBody();
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new VendorBadServerAnswerException('No response from Atol. ' . $url);
        }

        if (!in_array($curl->getStatus(), self::ALLOWED_HTTP_STATUSES)) {
            throw new VendorBadServerAnswerException(
                'Bad response from Atol. Status: ' . $curl->getStatus()
                . ($response ? '. Response: ' . (string) $response : '')
            );
        }

        if (!$curl->getBody()) {
            throw new VendorBadServerAnswerException('Empty response from Atol.');
        }

        return $response;
    }

    /**
     * @param string $url
     * @param string $token
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @return string
     */
    protected function sendGetRequest(string $url, string $token): string
    {
        try {
            $curl = $this->curlClientFactory->create();
            $curl->addHeader('Token', $token);
            $curl->get($url);
            $response = $curl->getBody();
        } catch (\Exception $e) {
            throw new VendorBadServerAnswerException('No response from Atol: ' . $e->getMessage());
        }

        if (!in_array($curl->getStatus(), self::ALLOWED_HTTP_STATUSES)) {
            throw new VendorBadServerAnswerException(
                'Bad response from Atol. Status: ' . $curl->getStatus()
                . ($response ? '. Response: ' . (string) $response : '')
            );
        }

        if (!$curl->getBody()) {
            throw new VendorBadServerAnswerException('Empty response from Atol.');
        }

        return $response;
    }

    /**
     * @param int|string|null $storeId
     * @throws \Exception
     * @return string
     */
    private function getGroupCode($storeId = null)
    {
        $groupCode = $this->kkmHelper->getConfig('atol/group_code', $storeId);
        if (!$groupCode) {
            throw new \Exception(
                'No groupCode. Please set up the module properly.'
            );
        }

        return $groupCode;
    }
}
