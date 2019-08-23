<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1\TransactionDetailsType\EmvDetailsAType;

/**
 * Class representing TagAType
 */
class TagAType
{
    /**
     * @property string $tagId
     */
    private $tagId = null;
    /**
     * @property string $data
     */
    private $data = null;
    /**
     * Gets as tagId
     *
     * @return string
     */
    public function getTagId()
    {
        return $this->tagId;
    }
    /**
     * Sets a new tagId
     *
     * @param string $tagId
     * @return self
     */
    public function setTagId($tagId)
    {
        $this->tagId = $tagId;
        return $this;
    }
    /**
     * Gets as data
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }
    /**
     * Sets a new data
     *
     * @param string $data
     * @return self
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }
}

?>