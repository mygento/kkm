<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Crontab;

class Update
{
    /** @var \Mygento\Kkm\Helper\Data */
    private $kkmHelper;
    /**
     * @var \Mygento\Kkm\Model\VendorInterface
     */
    private $vendor;
    /**
     * @var \Mygento\Kkm\Helper\Transaction\Proxy
     */
    private $transactionHelper;

    public function __construct(
        \Mygento\Kkm\Helper\Data $kkmHelper,
        \Mygento\Kkm\Helper\Transaction\Proxy $transactionHelper,
        \Mygento\Kkm\Model\VendorInterface $vendor
    ) {
        $this->kkmHelper = $kkmHelper;
        $this->vendor = $vendor;
        $this->transactionHelper = $transactionHelper;
    }

    /**
     * @throws \Exception
     */
    public function execute()
    {
        //Проверка включения Cron
        if (!$this->kkmHelper->getConfig('general/update_cron')) {
            return;
        }

        $this->kkmHelper->info('KKM Update statuses Cron START');

        $uuids = $this->transactionHelper->getAllWaitUuids();

        $result = [];
        $i      = 0;
        foreach ($uuids as $uuid) {
            $response = $this->vendor->updateStatus($uuid);

            $result[] = "UUID {$uuid} new status: {$response->getStatus()}";
            $i++;
        }

        $this->kkmHelper->debug($result);
        $this->kkmHelper->info("{$i} transactions updated");
        $this->kkmHelper->info('KKM Update statuses Cron END');
    }
}
