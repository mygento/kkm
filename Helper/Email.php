<?php

/**
 * @author Mygento Team
 * @copyright 2017-2019 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Helper;

/**
 * @SuppressWarnings(PHPMD.ShortVariable)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Email
{
    // phpcs:disable
    private $template;

    private $area;

    private $fields;

    private $sender = [];

    private $recipient = [];

    // phpcs:enable

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    private $inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Email constructor.
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
    }

    /**
     * @param string $email
     * @param string $name
     * @return $this
     */
    public function setSender($email, $name)
    {
        $this->sender['email'] = $email;
        $this->sender['name'] = $name;

        return $this;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setRecipient($email)
    {
        $this->recipient['email'] = $email;

        return $this;
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function setFields($fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * @param string $template
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Set area (email template belongs to) see constants in \Magento\Framework\App\Area
     * @param string $area
     * @return $this
     */
    public function setArea($area)
    {
        $this->area = $area;

        return $this;
    }

    /**
     * @throws \Magento\Framework\Exception\MailException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return bool
     */
    public function send()
    {
        $this->inlineTranslation->suspend();

        $postObject = new \Magento\Framework\DataObject();
        $postObject->setData($this->fields);

        $this->transportBuilder
            ->setTemplateIdentifier($this->template)
            ->setTemplateOptions(
                [
                    'area' => $this->area,
                    'store' => $this->storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars(['data' => $postObject])
            ->setFrom($this->sender)
            ->addTo($this->recipient);

        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();

        return true;
    }
}
