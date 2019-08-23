<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Log;

interface RegisterInterface
{
    public function __toString();
    public function toArray();
    public function toJson();
    public function getName();
    public function setName($name);
    public function getNamespace();
    public function setNamespace($key);
    public function getNamespaceId();
    public function setNamespaceId($id);
    public function setValue($value);
    public function getValue();
    public function write($value);
    public function latestByNamespaces(array $namespaces, $id);
}

?>