<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\CheckOnline;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
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
    const ALLOWED_HTTP_STATUSES = [100, 200, 400, 401, 500];

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
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var File
     */
    private $file;


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
    public function sendSell($request): ResponseInterface
    {
        return $this->sendPostRequest($request, 'Invoice');
    }

    /**
     * @param RequestInterface $request
     * @param string $operationType
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @return string
     */
    protected function sendPostRequest($request, $operationType): ResponseInterface
    {
        $entityStoreId = $request->getEntityStoreId();
        $debugData = [];
        $url = $debugData['url'] = $this->getUrl($entityStoreId);
        $this->kkmHelper->info('START Sending ' . $operationType);
        $this->kkmHelper->debug('URL: ' . $url);
        $this->kkmHelper->debug('Request:', $request->__toArray());

        $params = $debugData['request'] = $this->jsonSerializer->serialize($request);

        try {
            $curl = $this->curlClientFactory->create();
            $curl->addHeader('Content-Type', 'application/json; charset=utf-8');
            $curl->setOption(CURLOPT_SSLCERT, $this->getFilePath('certificate', $entityStoreId));
            $curl->setOption(CURLOPT_SSLKEY, $this->getFilePath('key', $entityStoreId));
            $curl->post($url, $params);
            $responseRaw = $curl->getBody();

//            $response = $this->responseFactory->create(['jsonRaw' => $responseRaw]);
            $response = $responseRaw;

//            $this->kkmHelper->info(__('Invoice is sent. Uuid: %1', $response->getUuid()));
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

        if (!in_array($curl->getStatus(), self::ALLOWED_HTTP_STATUSES)) {
            throw new VendorBadServerAnswerException(
                'Bad response from Checkonline. Status: ' . $curl->getStatus()
                . ($response ? '. Response: ' . (string) $response : '')
            );
        }

        if (!$curl->getBody()) {
            throw new VendorBadServerAnswerException('Empty response from Checkonline.');
        }

        return $response;
    }

    /**
     * @param $fileType
     * @param null $storeId
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
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

        return $this->kkmHelper->getConfig($configPath, $storeId);
    }
}
