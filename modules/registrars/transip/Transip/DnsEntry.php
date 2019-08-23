<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

class Transip_DnsEntry
{
    public $name = NULL;
    public $expire = NULL;
    public $type = NULL;
    public $content = NULL;
    const TYPE_A = "A";
    const TYPE_AAAA = "AAAA";
    const TYPE_CNAME = "CNAME";
    const TYPE_MX = "MX";
    const TYPE_NS = "NS";
    const TYPE_TXT = "TXT";
    const TYPE_SRV = "SRV";
    public function __construct($name, $expire, $type, $content)
    {
        $this->name = $name;
        $this->expire = $expire;
        $this->type = $type;
        $this->content = $content;
    }
}

?>