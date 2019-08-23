<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1\MessagesType;

/**
 * Class representing MessageAType
 */
class MessageAType
{
    /**
     * @property string $code
     */
    private $code = null;
    /**
     * @property string $text
     */
    private $text = null;
    /**
     * Gets as code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }
    /**
     * Sets a new code
     *
     * @param string $code
     * @return self
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }
    /**
     * Gets as text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }
    /**
     * Sets a new text
     *
     * @param string $text
     * @return self
     */
    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }
}

?>