<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Environment\Ioncube\Inspector\Filter;

abstract class AbstractCacheIterator extends \CachingIterator implements \WHMCS\Environment\Ioncube\Contracts\InspectorFilterIteratorInterface
{
    private $phpVersion = NULL;
    public function __construct($phpVersion, \WHMCS\Environment\Ioncube\Contracts\InspectorIteratorInterface $iterator, $flags = self::FULL_CACHE)
    {
        $this->setPhpVersion($phpVersion);
        $iterator = $this->getFilterIterator($iterator->getIterator());
        parent::__construct($iterator, $flags);
    }
    public function getPhpVersion()
    {
        return $this->phpVersion;
    }
    public function setPhpVersion($phpVersion)
    {
        $this->phpVersion = $phpVersion;
        return $this;
    }
    public function getFilterIterator(\Iterator $iterator)
    {
        return new \CallbackFilterIterator($iterator, array($this, "accept"));
    }
    public abstract function accept(\WHMCS\Environment\Ioncube\Contracts\InspectedFileInterface $current);
}

?>