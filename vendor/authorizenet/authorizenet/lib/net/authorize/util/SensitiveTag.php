<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\util;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\SerializedName;
$type = new Type();
$serializedName = new SerializedName(array("value" => "Loading-SerializedName-Class"));
//to do: use Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace to auto load classes
class SensitiveTag
{
    /**
     * @Type("string")
     * @SerializedName("tagName")
     */
    public $tagName;
    /**
     * @Type("string")
     * @SerializedName("pattern")
     */
    public $pattern;
    /**
     * @Type("string")
     * @SerializedName("replacement")
     */
    public $replacement;
    /**
     * @Type("boolean")
     * @SerializedName("disableMask")
     */
    public $disableMask;
    public function __construct($tagName, $pattern = "", $replace = "", $disableMask = false)
    {
        $this->tagName = $tagName;
        $this->pattern = $pattern;
        $this->replacement = $replace;
        $this->disableMask = $disableMask;
    }
}

?>