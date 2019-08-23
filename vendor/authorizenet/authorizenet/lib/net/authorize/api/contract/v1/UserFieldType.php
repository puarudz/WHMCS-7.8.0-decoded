<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1;

/**
 * Class representing UserFieldType
 *
 *
 * XSD Type: userField
 */
class UserFieldType
{
    /**
     * @property string $name
     */
    private $name = null;
    /**
     * @property string $value
     */
    private $value = null;
    /**
     * Gets as name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * Sets a new name
     *
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    /**
     * Gets as value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
    /**
     * Sets a new value
     *
     * @param string $value
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
}

?>