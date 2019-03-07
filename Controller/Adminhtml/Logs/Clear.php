<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Controller\Adminhtml\Logs;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Crutch Class to Clear logs
 * @package Mygento\Kkm\Controller\Adminhtml\Logs
 */
class Clear extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $directoryList;
    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    private $ioFile;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Io\File $ioFile
    ) {
        $this->directoryList = $directoryList;
        $this->ioFile        = $ioFile;

        parent::__construct($context);
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $file = \Mygento\Kkm\Helper\Data::CONFIG_CODE . '.log';

        $filepath = $this->directoryList->getPath(DirectoryList::VAR_DIR)
            . DIRECTORY_SEPARATOR . DirectoryList::LOG
            . DIRECTORY_SEPARATOR . $file;

        try {
            if (!$this->ioFile->fileExists($filepath)) {
                throw new \Exception('Logs not found');
            }

            $this->ioFile->rm($filepath);
            $this->getMessageManager()->addSuccessMessage(
                __('Logs have been cleared')
            );
        } catch (\Exception $exc) {
            $this->getMessageManager()->addErrorMessage($exc->getMessage());
        }

        return $this->resultRedirectFactory->create()->setUrl(
            $this->_redirect->getRefererUrl()
        );
    }
}
