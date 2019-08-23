<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Mail;

class Message
{
    protected $type = "general";
    protected $templateName = "";
    protected $from = array();
    protected $to = array();
    protected $cc = array();
    protected $bcc = array();
    protected $subject = "";
    protected $body = "";
    protected $bodyPlainText = "";
    protected $attachments = array();
    const HEADER_MARKER = "<!-- message header end -->";
    const FOOTER_MARKER = "<!-- message footer start -->";
    public function __construct()
    {
        $this->setFromName(\WHMCS\Config\Setting::getValue("CompanyName"));
        $this->setFromEmail(\WHMCS\Config\Setting::getValue("Email"));
    }
    public static function createFromTemplate(Template $template)
    {
        $message = new self();
        $message->setType($template->type);
        $message->setTemplateName($template->name);
        if ($template->fromName) {
            $message->setFromName($template->fromName);
        }
        if ($template->fromEmail) {
            $message->setFromEmail($template->fromEmail);
        }
        $message->setSubject($template->subject);
        if ($template->plaintext) {
            $message->setPlainText($template->message);
        } else {
            $message->setBodyAndPlainText($template->message);
        }
        if (is_array($template->copyTo)) {
            foreach ($template->copyTo as $copyto) {
                $message->addRecipient("cc", $copyto);
            }
        }
        if (is_array($template->blindCopyTo)) {
            foreach ($template->blindCopyTo as $bcc) {
                $message->addRecipient("bcc", $bcc);
            }
        }
        if (is_array($template->attachments)) {
            $storage = \Storage::emailTemplateAttachments();
            foreach ($template->attachments as $attachment) {
                $displayname = substr($attachment, 7);
                try {
                    $message->addStringAttachment($displayname, $storage->read($attachment));
                } catch (\League\Flysystem\FileNotFoundException $e) {
                    $message = "Could not access file: " . $attachment;
                    logActivity("Email Sending Failed - " . $message . " (Subject: " . $template->subject . ")", "none");
                    throw new \WHMCS\Exception\Mail\InvalidTemplate("Could not access file: " . $attachment);
                }
            }
        }
        return $message;
    }
    public function setType($type)
    {
        $this->type = $type;
    }
    public function getType()
    {
        return $this->type;
    }
    public function setTemplateName($templateName)
    {
        $this->templateName = $templateName;
    }
    public function getTemplateName()
    {
        return $this->templateName;
    }
    public function addRecipient($kind, $email, $name = "")
    {
        if (in_array($kind, array("to", "cc", "bcc"))) {
            array_push($this->{$kind}, array($email, $name));
        }
        return $this;
    }
    public function clearRecipients($kind)
    {
        if (in_array($kind, array("to", "cc", "bcc"))) {
            $this->{$kind} = array();
        }
        return $this;
    }
    public function setFromName($name)
    {
        $this->from["name"] = $name;
    }
    public function getFromName()
    {
        return $this->from["name"];
    }
    public function setFromEmail($email)
    {
        $this->from["email"] = $email;
    }
    public function getFromEmail()
    {
        return $this->from["email"];
    }
    public function getRecipients($kind)
    {
        if (in_array($kind, array("to", "cc", "bcc"))) {
            return $this->{$kind};
        }
    }
    public function getFormattedRecipients($kind)
    {
        if (in_array($kind, array("to", "cc", "bcc"))) {
            $recipients = array();
            foreach ($this->{$kind} as $recipient) {
                if ($recipient[1]) {
                    $recipients[] = $recipient[1] . " <" . $recipient[0] . ">";
                } else {
                    $recipients[] = $recipient[0];
                }
            }
            return $recipients;
        } else {
            return "";
        }
    }
    public function setSubject($subject)
    {
        $this->subject = \WHMCS\Input\Sanitize::decode($subject);
    }
    public function getSubject()
    {
        return $this->subject;
    }
    public function setBodyAndPlainText($body)
    {
        $this->setBody($body)->setPlainText($body);
    }
    public function setBody($body)
    {
        if ($this->getType() == "admin") {
            $adminNotification = new AdminNotification();
            $body = $adminNotification->getPreparedHtml($this->getSubject(), $body);
        } else {
            $globalHeader = \WHMCS\Config\Setting::getValue("EmailGlobalHeader");
            $globalFooter = \WHMCS\Config\Setting::getValue("EmailGlobalFooter");
            $messageHeader = $globalHeader ? \WHMCS\Input\Sanitize::decode($globalHeader) . "\n" . self::HEADER_MARKER : "";
            $messageFooter = $globalFooter ? self::FOOTER_MARKER . "\n" . \WHMCS\Input\Sanitize::decode($globalFooter) : "";
            $body = $messageHeader . $body . $messageFooter;
        }
        $this->body = $body;
        return $this;
    }
    public function setBodyFromSmarty($body)
    {
        $this->body = $body;
    }
    public function getBody()
    {
        $body = $this->body;
        if (!$body) {
            return $body;
        }
        if (strpos($body, "[EmailCSS]") !== false) {
            if ($this->getType() == "admin") {
                $body = str_replace("[EmailCSS]", AdminNotification::getCssStyling(), $body);
            } else {
                $body = str_replace("[EmailCSS]", \WHMCS\Config\Setting::getValue("EmailCSS"), $body);
            }
        } else {
            $body = "<style>" . PHP_EOL . \WHMCS\Config\Setting::getValue("EmailCSS") . PHP_EOL . "</style>" . PHP_EOL . $body;
        }
        return $body;
    }
    public function getBodyWithoutCSS()
    {
        return $this->body;
    }
    public function setPlainText($text)
    {
        $text = \WHMCS\Input\Sanitize::decode($text);
        $text = str_replace(array("\r\n</p>\r\n<p>\r\n", "\n</p>\n<p>\n"), "\n\n", $text);
        $text = str_replace(array("<br />\r\n", "<br />\n", "<br>\r\n", "<br>\n"), "\n", $text);
        $text = str_replace("<p>", "", $text);
        $text = str_replace("</p>", "\n\n", $text);
        $text = str_replace("<br>", "\n", $text);
        $text = str_replace("<br />", "\n", $text);
        $text = $this->replaceLinksWithUrl($text);
        $text = strip_tags($text);
        $this->bodyPlainText = trim($text);
        return $this;
    }
    protected function replaceLinksWithUrl($text)
    {
        return preg_replace("/<a.*?href=([\\\"])(.*?)\\1.*?<\\/a>/", "\$2", $text);
    }
    public function getPlainText()
    {
        return $this->bodyPlainText;
    }
    public function addStringAttachment($filename, $data)
    {
        $this->attachments[] = array("filename" => $filename, "data" => $data);
    }
    public function addFileAttachment($filename, $filepath)
    {
        $this->attachments[] = array("filename" => $filename, "filepath" => $filepath);
    }
    public function getAttachments()
    {
        return $this->attachments;
    }
    public function hasRecipients()
    {
        return 0 < count($this->to) + count($this->cc) + count($this->bcc);
    }
    public function saveToEmailLog($userId)
    {
        $emailData = array("userid" => $userId, "date" => "now()", "to" => implode(", ", $this->getFormattedRecipients("to")), "cc" => implode(", ", $this->getFormattedRecipients("cc")), "bcc" => implode(", ", $this->getFormattedRecipients("bcc")), "subject" => $this->getSubject(), "message" => $this->getBody() ?: $this->getPlainText());
        $results = run_hook("EmailPreLog", $emailData);
        foreach ($results as $hookReturn) {
            if (!is_array($hookReturn)) {
                continue;
            }
            foreach ($hookReturn as $key => $value) {
                if ($key == "abortLogging" && $value === true) {
                    return false;
                }
                if (array_key_exists($key, $emailData)) {
                    $emailData[$key] = $value;
                }
            }
        }
        return insert_query("tblemails", $emailData);
    }
}

?>