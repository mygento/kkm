<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\CheckOnline;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Mygento\Kkm\Api\Data\RequestInterface;
use Mygento\Kkm\Api\Data\ResponseInterface;
use Mygento\Kkm\Exception\CreateDocumentFailedException;
use Mygento\Kkm\Exception\VendorBadServerAnswerException;

class Client
{
    const CERT_STORAGE_DIR = [
        'prod' => 'kkm/certs/prod',
        'test' => 'kkm/certs/test',
    ];

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    private $kkmHelper;

    /**
     * @var \Magento\Framework\HTTP\Client\CurlFactory
     */
    private $curlClientFactory;

    /**
     * @var \Mygento\Kkm\Model\CheckOnline\ResponseFactory
     */
    private $responseFactory;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var File
     */
    private $file;

    private $apiUrlPath = 'fr/api/v2/Complex';

    /**
     * Client constructor.
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Mygento\Kkm\Model\CheckOnline\ResponseFactory $responseFactory
     * @param \Magento\Framework\HTTP\Client\CurlFactory $curlFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     */
    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Model\CheckOnline\ResponseFactory $responseFactory,
        \Magento\Framework\HTTP\Client\CurlFactory $curlFactory,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        DirectoryList $directoryList,
        File $file
    ) {
        $this->kkmHelper = $kkmHelper;
        $this->responseFactory = $responseFactory;
        $this->curlClientFactory = $curlFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->directoryList = $directoryList;
        $this->file = $file;
    }

    /**
     * @param RequestInterface $request
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @return string
     */
    public function sendPostRequest($request): ResponseInterface
    {
        $entityStoreId = $request->getEntityStoreId();
        $debugData = [];
        $url = $debugData['url'] = $this->getUrl($entityStoreId);
        $this->kkmHelper->info('START Sending ' . $request->getEntityType());
        $this->kkmHelper->debug('URL: ' . $url);
        $this->kkmHelper->debug('Request:', $request->__toArray());

        $params = $debugData['request'] = $this->jsonSerializer->serialize($request);

        try {
            $curl = $this->curlClientFactory->create();
            $curl->addHeader('Content-Type', 'application/json; charset=utf-8');
            $curl->setOption(CURLOPT_SSLCERT, $this->getFilePath('certificate', $entityStoreId));
            $curl->setOption(CURLOPT_SSLKEY, $this->getFilePath('key', $entityStoreId));
            $curl->post($url, $params);

            if (!$curl->getBody()) {
                throw new VendorBadServerAnswerException('Empty response from Checkonline.');
            }

            $responseRaw = $curl->getBody();
            $this->validateVendorAnswer($responseRaw);
            $response = $this->responseFactory->create(['jsonRaw' => $responseRaw]);

            if (!$response->getRequestId()) {
                $response->setRequestId($request->getExternalId());
            }

            $this->kkmHelper->info(
                __('%1 is sent. RequestId: %2', $request->getEntityType(), $response->getIdForTransaction())
            );
            $this->kkmHelper->debug('Response:', [$response]);
        } catch (FileSystemException $e) {
            throw new CreateDocumentFailedException(
                $e->getMessage(),
                $response ?? null,
                $debugData
            );
        } catch (\Exception $e) {
            throw new VendorBadServerAnswerException(
                sprintf('Error while sending request to Checkonline: %s. Url: %s', $e->getMessage(), $url)
            );
        }

        return $response;
    }

    /**
     * @param string $fileType
     * @param null $storeId
     * @throws \Magento\Framework\Exception\FileSystemException
     * @return string
     */
    private function getFilePath($fileType, $storeId = null): string
    {
        $isTestMode = $this->kkmHelper->isCheckonlineTestMode($storeId);
        $mode = $isTestMode ? 'test' : 'prod';

        switch ($fileType) {
            case 'certificate':
                $configPath = $isTestMode ? 'checkonline/test_cert' : 'checkonline/cert';
                break;
            case 'key':
                $configPath = $isTestMode ? 'checkonline/test_private_key' : 'checkonline/private_key';
                break;
        }

        $filePathInVar = self::CERT_STORAGE_DIR[$mode] . '/' . $this->kkmHelper->getConfig($configPath, $storeId);
        $fileAbsolutePath = $this->directoryList->getPath('var') . '/' . $filePathInVar;

        if (!$this->file->isFile($fileAbsolutePath) || !$this->file->isExists($fileAbsolutePath)) {
            throw new FileSystemException(
                __(sprintf('The %s file does not exists in var/%s directory', $fileType, $filePathInVar))
            );
        }

        return $fileAbsolutePath;
    }

    /**
     * @param string|null $storeId
     * @return string
     */
    private function getUrl($storeId = null): string
    {
        $configPath = $this->kkmHelper->isCheckonlineTestMode($storeId)
            ? 'checkonline/test_api_url' : 'checkonline/api_url';

        return rtrim($this->kkmHelper->getConfig($configPath, $storeId), '/') . '/' . $this->apiUrlPath;
    }

    /**
     * @param string $rawResponse
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     */
    private function validateVendorAnswer($rawResponse)
    {
        if (!json_decode($rawResponse)) {
            throw new VendorBadServerAnswerException(
                __('Response from Checkonline is not valid. Response: %1', (string) $rawResponse)
            );
        }
    }
}
