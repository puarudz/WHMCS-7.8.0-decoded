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
class SensitiveDataConfigType
{
    /**
     * @Type("array<net\authorize\util\SensitiveTag>")
     * @SerializedName("sensitiveTags")
     */
    public $sensitiveTags;
    /**
     * @Type("array<string>")
     * @SerializedName("sensitiveStringRegexes")
     */
    public $sensitiveStringRegexes;
}

?>