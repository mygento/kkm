<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Controller\Adminhtml\Logs;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Workaround Class to Download file with logs
 * @package Mygento\Kkm\Controller\Adminhtml\Logs
 */
class Download extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $directoryList;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    private $fileFactory;

    /**
     * Download constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param DirectoryList $directoryList
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    ) {
        $this->directoryList = $directoryList;
        $this->fileFactory = $fileFactory;

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
            return $this->fileFactory->create(
                $file,
                [
                    'type' => 'filename',
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
