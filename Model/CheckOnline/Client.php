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
    private const TEST_CERT_STORAGE_DIR = 'kkm/certs/test';
    private const PROD_CERT_STORAGE_DIR = 'kkm/certs/prod';
    private const DIRECTORY_VAR = 'var';

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
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Mygento\Kkm\Model\CheckOnline\ResponseFactory $responseFactory
     * @param \Magento\Framework\HTTP\Client\CurlFactory $curlFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param DirectoryList $directoryList
     * @param File $file
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
     * @return ResponseInterface
     */
    public function sendPostRequest($request): ResponseInterface
    {
        $entityStoreId = $request->getStoreId();
        $debugData = [];
        $url = $debugData['url'] = $this->getUrl($entityStoreId);
        $this->kkmHelper->info('START Sending ' . $request->getEntityType());
        $this->kkmHelper->debug('URL: ' . $url);
        $this->kkmHelper->debug('Request:', $request->__toArray());

        $params = $debugData['request'] = $this->jsonSerializer->serialize($request);
        $response = null;
        $responseRaw = '';

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
            $decodedResponse = json_decode($responseRaw, false, 512, JSON_THROW_ON_ERROR);
            $response = $this->responseFactory->create(['decodedResponse' => $decodedResponse]);

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
                $response,
                $debugData
            );
        } catch (\JsonException $e) {
            throw new VendorBadServerAnswerException(
                __('Response from Checkonline is not valid. Response: %1', $responseRaw)
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
    private function getFilePath(string $fileType, $storeId = null): string
    {
        $isTestMode = $this->kkmHelper->isTestMode($storeId);

        switch ($fileType) {
            case 'key':
                $fileName = $this->kkmHelper->getClientPrivateKeyFileName($storeId);
                break;
            case 'certificate':
            default:
                $fileName = $this->kkmHelper->getClientCertFileName($storeId);
        }

        $currentCertStorageDir = $isTestMode ? self::TEST_CERT_STORAGE_DIR : self::PROD_CERT_STORAGE_DIR;
        $filePathInVar = $currentCertStorageDir . '/' . $fileName;
        $fileAbsolutePath = $this->directoryList->getPath(self::DIRECTORY_VAR) . '/' . $filePathInVar;

        if (!$this->file->isExists($fileAbsolutePath) || !$this->file->isFile($fileAbsolutePath)) {
            throw new FileSystemException(
                __(sprintf(
                    'The %s file \'%s\' does not exists in \'var/%s\' directory',
                    $fileType,
                    $fileName,
                    $currentCertStorageDir
                ))
            );
        }

        return $fileAbsolutePath;
    }

    /**
     * @param string|null $storeId
     * @return string
     */
    private function getUrl(?string $storeId = null): string
    {
        return rtrim($this->kkmHelper->getCheckonlineApiUrl($storeId), '/') . '/' . $this->apiUrlPath;
    }
}
