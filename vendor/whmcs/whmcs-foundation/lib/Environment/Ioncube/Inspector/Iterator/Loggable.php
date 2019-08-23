<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Environment\Ioncube\Inspector\Iterator;

class Loggable extends AbstractInspectorIterator
{
    public static function fromDatabase()
    {
        return (new static())->exchangeWithLog();
    }
    protected function exchangeWithLog()
    {
        $items = array();
        $logged = \WHMCS\Environment\Ioncube\Log\File::all();
        foreach ($logged as $file) {
            $items[$file->getFileFingerprint()] = $file;
        }
        $this->exchangeArray($items);
        return $this;
    }
    public function getLastScanTime()
    {
        return \WHMCS\Config\Setting::getValue("PhpCompatLastScanTime");
    }
    public function setLastScanTime(\WHMCS\Carbon $dateTime)
    {
        \WHMCS\Config\Setting::setValue("PhpCompatLastScanTime", $dateTime->toDateTimeString());
        return $this;
    }
    public function nullifyLastScanTime()
    {
        \WHMCS\Config\Setting::setValue("PhpCompatLastScanTime", "");
        return $this;
    }
    public function purgeAll()
    {
        \WHMCS\Environment\Ioncube\Log\File::query()->delete();
        return $this;
    }
    public function save()
    {
        (new \WHMCS\Environment\Ioncube\Log\File())->replaceAll($this->getArrayCopy());
        $this->setLastScanTime(\WHMCS\Carbon::now());
        return $this;
    }
}

?>