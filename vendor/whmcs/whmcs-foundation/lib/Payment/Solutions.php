<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment;

class Solutions extends \ArrayObject
{
    protected $iterator = NULL;
    const TYPE_GATEWAY = "gateway";
    const TYPE_ALTERNATE = "alternate";
    const TYPE_MULTI = "allinone";
    public function __construct()
    {
        return $this;
    }
    public function getIterator()
    {
        if (!$this->iterator) {
            $this->restoreDefaultIterator();
        }
        return $this->iterator;
    }
    protected function restoreDefaultIterator()
    {
        $this->setIterator(new \ArrayIterator($this->getArrayCopy()));
        foreach ($this->iterator as $v) {
        }
        return $this;
    }
    public function setIterator(\Iterator $iterator)
    {
        if (!$iterator instanceof \CachingIterator) {
            $iterator = new \CachingIterator($iterator, \CachingIterator::FULL_CACHE);
        }
        foreach ($iterator as $v) {
        }
        $this->iterator = $iterator;
        return $this;
    }
    public function count()
    {
        return $this->getIterator()->count();
    }
    public function loadSolutionsInDirectory($directory)
    {
        $dirIterator = new \DirectoryIterator($directory);
        $solutionSet = array();
        foreach ($dirIterator as $resource) {
            if ($resource->isFile() && !$resource->isLink() && $resource->getExtension() == "php" && $resource->getBasename() != "index") {
                try {
                    $solution = $this->getAdapterFromFile($resource);
                    $solutionSet[$solution->getName()] = $solution;
                } catch (Exception\InvalidModuleException $e) {
                    trigger_error($e->getMessage(), E_USER_WARNING);
                }
            }
        }
        $this->exchangeArray($solutionSet);
        return $this;
    }
    public function getAdapterFromFile(\SplFileInfo $resource)
    {
        $basename = $resource->getBasename(".php");
        $resource_classname = $basename . "PaymentSolution";
        $moduleIncludeFile = $resource->getPathname();
        global $CONFIG;
        global $whmcs;
        global $GATEWAYMODULE;
        $whmcs = \WHMCS\Application::getInstance();
        $CONFIG = $whmcs->getApplicationConfig();
        include_once (string) $moduleIncludeFile;
        if (class_exists($resource_classname)) {
            $adapter = new $resource_classname();
            if (!$adapter instanceof Adapter\AdapterInterface) {
                throw new Exception\InvalidModuleException(sprintf("Payment solution module class '%s' does not implement %s", $resource_classname, "WHMCS\\Payment\\Adapter\\AdapterInterface"));
            }
        } else {
            $adapter = new Adapter\GatewaysModuleAdapter($basename);
        }
        return $adapter;
    }
    public static function getValidSolutionTypes()
    {
        return array(self::TYPE_MULTI, self::TYPE_GATEWAY, self::TYPE_ALTERNATE);
    }
    public static function isValidSolutionType($type)
    {
        $validOptions = self::getValidSolutionTypes();
        return in_array($type, $validOptions);
    }
    public function applyFilter(Filter\FilterInterface $filter)
    {
        $iterator = $filter->getFilteredIterator($this->getIterator());
        $this->setIterator($iterator);
        return $iterator;
    }
    public function removeAllFilters()
    {
        $filter = $this->getIterator()->getInnerIterator();
        $this->restoreDefaultIterator();
        return $filter;
    }
    public function removeFilter()
    {
        $iterator = $this->getIterator();
        $innerIterator = $iterator->getInnerIterator();
        if ($innerIterator instanceof \FilterIterator) {
            $iteratorToSet = $innerIterator->getInnerIterator();
            $iteratorToReturn = $innerIterator;
        } else {
            $iteratorToReturn = $iteratorToSet = $innerIterator;
        }
        $this->setIterator($iteratorToSet);
        return $iteratorToReturn;
    }
}

?>