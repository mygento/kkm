<?php

/**
 * @author Mygento Team
 * @copyright 2017-2021 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Console;

use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Mygento\Kkm\Api\Processor\UpdateInterface;
use Mygento\Kkm\Helper\Data;
use Mygento\Kkm\Helper\Request;
use Mygento\Kkm\Helper\Transaction\Proxy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractUpdateStatus extends Command
{
    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Mygento\Kkm\Helper\Transaction\Proxy
     */
    protected $transactionHelper;

    /**
     * @var \Mygento\Kkm\Api\Processor\UpdateInterface
     */
    protected $updateProcessor;

    /**
     * @var \Mygento\Kkm\Helper\Data
     */
    protected $kkmHelper;

    /**
     * @var \Mygento\Kkm\Helper\Request
     */
    protected $requestHelper;

    /**
     * AbstractUpdateStatus constructor.
     * @param \Mygento\Kkm\Api\Processor\UpdateInterface $updateProcessor
     * @param \Mygento\Kkm\Helper\Transaction\Proxy $transactionHelper
     * @param \Magento\Framework\App\State $state
     * @param \Mygento\Kkm\Helper\Data $kkmHelper
     * @param \Mygento\Kkm\Helper\Request $requestHelper
     */
    public function __construct(
        UpdateInterface $updateProcessor,
        Proxy $transactionHelper,
        State $state,
        Data $kkmHelper,
        Request $requestHelper
    ) {
        parent::__construct();

        $this->appState = $state;
        $this->updateProcessor = $updateProcessor;
        $this->transactionHelper = $transactionHelper;
        $this->kkmHelper = $kkmHelper;
        $this->requestHelper = $requestHelper;
    }

    /**
     * @param array $uuids
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function updateUuids($uuids, OutputInterface $output)
    {
        $i = 1;
        foreach ($uuids as $uuid) {
            $output->writeln("<comment>${i} Updating {$uuid} ...</comment>");
            $this->updateOne($output, $uuid);
            $i++;
        }
    }

    /**
     * @param $storeId
     * @return string
     */
    protected function getNoNeedUpdateMessage($storeId)
    {
        return sprintf(
            "<info>Vendor '%s' which configured to store with id '%s' does not need update status.</info>",
            $this->kkmHelper->getCurrentVendorCode($storeId),
            $storeId
        );
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string $uuid
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mygento\Kkm\Exception\VendorBadServerAnswerException
     * @return int
     */
    private function updateOne($output, $uuid)
    {
        //Обновление статуса
        $response = $this->updateProcessor->proceedSync($uuid);

        if ($response->isFailed() || $response->getError()) {
            $output->writeln("<error>Status: {$response->getStatus()}</error>");
            $output->writeln("<error>Uuid: {$response->getIdForTransaction()}</error>");
            $output->writeln("<error>Text: {$response->getErrorMessage()}</error>");

            return Cli::RETURN_FAILURE;
        }

        $output->writeln("<info>Status: {$response->getStatus()}</info>");
        $output->writeln("<info>Uuid: {$response->getIdForTransaction()}</info>");

        return Cli::RETURN_SUCCESS;
    }
}
