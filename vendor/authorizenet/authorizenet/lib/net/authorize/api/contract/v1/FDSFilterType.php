<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1;

/**
 * Class representing FDSFilterType
 *
 *
 * XSD Type: FDSFilterType
 */
class FDSFilterType
{
    /**
     * @property string $name
     */
    private $name = null;
    /**
     * @property string $action
     */
    private $action = null;
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
     * Gets as action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }
    /**
     * Sets a new action
     *
     * @param string $action
     * @return self
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }
}

?>