<?php

namespace ChurchCRM\Emails;

use ChurchCRM\dto\SystemConfig;
use ChurchCRM\dto\SystemURLs;
use ChurchCRM\dto\ChurchMetaData;
use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

abstract class BaseEmail
{
    /** @var \PHPMailer */
    protected $mail;
    protected $mustache;

    public function __construct($toAddresses)
    {
        $this->setConnection();
        $this->mail->setFrom(ChurchMetaData::getChurchEmail(), ChurchMetaData::getChurchName());
        foreach ($toAddresses as $email) {
            $this->mail->addAddress($email);
        }

        // use .html instead of .mustache for default template extension
        $options = array('extension' => '.html');

        $this->mustache = new Mustache_Engine(array(
            'loader' => new Mustache_Loader_FilesystemLoader(SystemURLs::getDocumentRoot() . '/views/email', $options),
        ));
    }

    private function setConnection()
    {

        $this->mail = new \PHPMailer();
        $this->mail->IsSMTP();
        $this->mail->CharSet = 'UTF-8';
        $this->mail->Timeout = intval(SystemConfig::getValue("iSMTPTimeout"));
        $this->mail->Host = SystemConfig::getValue("sSMTPHost");
        if (SystemConfig::getBooleanValue("sSMTPAuth")) {
            $this->mail->SMTPAuth = true;
            $this->mail->Username = SystemConfig::getValue("sSMTPUser");
            $this->mail->Password = SystemConfig::getValue("sSMTPPass");
        }
        //$this->mail->SMTPDebug = 2;
    }

    public function send()
    {
        return $this->mail->send();
    }

    public function getError()
    {
        return $this->mail->ErrorInfo;
    }

    public function addStringAttachment($string, $filename)
    {
        $this->mail->addStringAttachment($string, $filename);
    }

    protected function buildMessage()
    {
        return $this->mustache->render($this->getMustacheTemplateName(), $this->getTokens());
    }

    protected function getMustacheTemplateName()
    {
        return "BaseEmail";
    }

    protected function getCommonTokens() {
        return [
          "toEmails" => $this->mail->getToAddresses(),
            "ChurchName" => ChurchMetaData::getChurchName(),
            "ChurchAddress" => ChurchMetaData::getChurchFullAddress(),
            "ChurchPhone" => ChurchMetaData::getChurchPhone(),
            "ChurchEmail" => ChurchMetaData::getChurchEmail(),
            "ChurchCRMURL" => SystemURLs::getURL(),
            "sDear" => SystemConfig::getValue('sDear'),
            "sConfirmSincerely" => SystemConfig::getValue('sConfirmSincerely'),
            "sConfirmSigner" => SystemConfig::getValue('sConfirmSigner')
        ];
    }

    abstract function getTokens();
}
