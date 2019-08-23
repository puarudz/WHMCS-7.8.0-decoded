<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Notification\Slack;

class Message
{
    public $channel = "";
    public $text = "";
    public $asUser = true;
    public $username = "";
    public $attachment = NULL;
    public function channel($channel)
    {
        $this->channel = trim($channel);
        return $this;
    }
    public function text($text)
    {
        $this->text = trim($text);
        return $this;
    }
    public function username($username)
    {
        $this->asUser = false;
        $this->username = trim($username);
        return $this;
    }
    public function attachment($attachment)
    {
        $this->attachment = $attachment;
        return $this;
    }
    public function toArray()
    {
        $message = array("channel" => $this->channel, "text" => $this->text, "as_user" => $this->asUser, "username" => $this->username);
        if (!empty($this->attachment)) {
            $message["attachments"] = json_encode(array($this->attachment->toArray()));
        }
        return $message;
    }
}

?>