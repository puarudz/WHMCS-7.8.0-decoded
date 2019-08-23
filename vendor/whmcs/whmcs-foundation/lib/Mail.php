<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Mail extends \PHPMailer\PHPMailer\PHPMailer
{
    protected $decodeAltBodyOnSend = true;
    protected static $validEncodings = array("8bit", "7bit", "binary", "base64", "quoted-printable");
    public function __construct($name = "", $email = "")
    {
        $whmcs = Application::getInstance();
        $whmcsAppConfig = $whmcs->getApplicationConfig();
        parent::__construct(true);
        $this->setSenderNameAndEmail($name, $email);
        if ($whmcs->get_config("MailType") == "mail") {
            $this->Mailer = "mail";
        } else {
            if ($whmcs->get_config("MailType") == "smtp") {
                $this->IsSMTP();
                $this->SMTPAutoTLS = false;
                $this->Host = $whmcs->get_config("SMTPHost");
                $this->Port = $whmcs->get_config("SMTPPort");
                $this->Hostname = $this->serverHostname();
                if ($whmcs->get_config("SMTPSSL")) {
                    $this->SMTPSecure = $whmcs->get_config("SMTPSSL");
                }
                if ($whmcs->get_config("SMTPUsername")) {
                    $this->SMTPAuth = true;
                    $this->Username = $whmcs->get_config("SMTPUsername");
                    $this->Password = decrypt($whmcs->get_config("SMTPPassword"));
                }
                if ($whmcsAppConfig["smtp_debug"]) {
                    $this->SMTPDebug = true;
                }
            }
        }
        $this->XMailer = $whmcs->get_config("CompanyName");
        $this->CharSet = $whmcs->get_config("Charset");
        $this->setEncoding($whmcs->get_config("MailEncoding"));
    }
    public function setSenderNameAndEmail($name, $email)
    {
        if (!$name) {
            $name = Config\Setting::getValue("CompanyName");
        }
        if (!$email) {
            $email = Config\Setting::getValue("Email");
        }
        $this->From = $email;
        $this->FromName = Input\Sanitize::decode($name);
        $this->Sender = $email;
        if (Config\Setting::getValue("MailType") == "smtp") {
            $this->clearReplyTos();
            if ($email != Config\Setting::getValue("SMTPUsername")) {
                $this->addReplyTo($email, $name);
            }
        }
        return $this;
    }
    protected function serverHostname()
    {
        $hostname = parent::serverHostname();
        if (!$hostname || ($hostname = "localhost.localdomain")) {
            $hostname = parse_url(Application::getInstance()->get_config("Domain"), PHP_URL_HOST);
        }
        return (string) $hostname;
    }
    public static function getValidEncodings()
    {
        return self::$validEncodings;
    }
    protected function setEncoding($config_value = 0)
    {
        $validEncodings = self::$validEncodings;
        if (isset($config_value) && !empty($validEncodings[$config_value])) {
            $this->Encoding = $validEncodings[$config_value];
        } else {
            $this->Encoding = $validEncodings[0];
        }
    }
    protected function addAnAddress($kind, $address, $name = "")
    {
        return parent::addAnAddress($kind, trim($address), Input\Sanitize::decode($name));
    }
    public function send()
    {
        $this->Subject = Input\Sanitize::decode($this->Subject);
        if ($this->decodeAltBodyOnSend) {
            $this->AltBody = Input\Sanitize::decode($this->AltBody);
        }
        return parent::send();
    }
    public function sendMessage(Mail\Message $message)
    {
        foreach ($message->getRecipients("to") as $to) {
            $this->AddAddress($to[0], $to[1]);
        }
        foreach ($message->getRecipients("cc") as $to) {
            if (Config\Setting::getValue("MailType") == "mail") {
                $this->AddAddress($to[0], $to[1]);
            } else {
                $this->AddCC($to[0], $to[1]);
            }
        }
        foreach ($message->getRecipients("bcc") as $to) {
            $this->AddBCC($to[0], $to[1]);
        }
        if (Config\Setting::getValue("BCCMessages")) {
            $bcc = Config\Setting::getValue("BCCMessages");
            $bcc = explode(",", $bcc);
            foreach ($bcc as $value) {
                if (trim($value)) {
                    $this->AddBCC($value);
                }
            }
        }
        $this->setSenderNameAndEmail($message->getFromName(), $message->getFromEmail());
        $this->Subject = $message->getSubject();
        $body = $message->getBody();
        $plainText = $message->getPlainText();
        if ($body) {
            $this->Body = $body;
            $this->AltBody = $plainText;
            if (!empty($this->Body) && empty($this->AltBody)) {
                $this->AltBody = " ";
            }
        } else {
            $this->Body = $plainText;
        }
        foreach ($message->getAttachments() as $attachment) {
            if (array_key_exists("data", $attachment)) {
                $attachment["filename"] = preg_replace("|[\\\\/]+|", "-", $attachment["filename"]);
                $this->AddStringAttachment($attachment["data"], $attachment["filename"]);
            } else {
                $this->addAttachment($attachment["filepath"], $attachment["filename"]);
            }
        }
        return $this->send();
    }
}

?>