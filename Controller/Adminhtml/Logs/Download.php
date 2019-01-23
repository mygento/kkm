<?php

namespace Mygento\Kkm\Controller\Adminhtml\Logs;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Crutch Class to Download file with logs
 * @package Mygento\Kkm\Controller\Adminhtml\Logs
 */
class Download extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $directory_list;
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    private $fileFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    ) {
        $this->directory_list = $directoryList;
        $this->fileFactory    = $fileFactory;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {
        $file = 'mygento_kkm.log';

        $filepath = $this->directory_list->getPath(DirectoryList::VAR_DIR)
            .DIRECTORY_SEPARATOR.DirectoryList::LOG
            .DIRECTORY_SEPARATOR.$file;

        try {
            return $this->fileFactory->create(
                $file,
                [
                    'type'  => 'filename',
                    'value' => $filepath,

                ],
                \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR,
                'application/text'
            );

        } catch (\Exception $exc) {
            $this->getMessageManager()->addErrorMessage($exc->getMessage());

            return $this->resultRedirectFactory->create()->setUrl(
                $this->_redirect->getRefererUrl()
            );
        }
    }
}