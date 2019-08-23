<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Utilities\System\PhpCompat;

class PhpCompatController
{
    public function index(\WHMCS\Http\Message\ServerRequest $request)
    {
        $view = new \WHMCS\Admin\ApplicationSupport\View\Html\Smarty\BodyContentWrapper();
        $view->setTitle(\AdminLang::trans("phpCompatUtil.title"))->setSidebarName("utilities")->setHelpLink("PHP Version Compatibility Assessment")->setFavicon("phpinfo");
        $iterator = \WHMCS\Environment\Ioncube\Inspector\Iterator\Loggable::fromDatabase();
        $lastScanned = $iterator->getLastScanTime();
        $needsInitialScan = false;
        if (!$lastScanned) {
            $needsInitialScan = true;
            $lastScanned = \AdminLang::trans("phpCompatUtil.never");
        }
        $templateData = array("assessments" => $this->getVersionDataWithAccordionCompat($iterator), "needsInitialScan" => $needsInitialScan, "lastScanned" => $lastScanned);
        $body = view("admin.utilities.system.php-compat.index", $templateData);
        $view->setBodyContent($body);
        return $view;
    }
    public function scan(\WHMCS\Http\Message\ServerRequest $request)
    {
        $config = \DI::make("config");
        if (isset($config["overidephptimelimit"]) && is_numeric($config["overidephptimelimit"])) {
            $timeout = (int) $config["overidephptimelimit"];
        } else {
            $timeout = 120;
        }
        @ini_set("max_execution_time", $timeout);
        $files = \WHMCS\Environment\Ioncube\Inspector\Iterator\Directory::fromDirectory(ROOTDIR);
        $iterator = \WHMCS\Environment\Ioncube\Inspector\Iterator\Loggable::fromDatabase();
        $iterator->merge($files);
        $iterator->save();
        $templateData = array("assessments" => $this->getVersionDataWithAccordionCompat($iterator));
        $body = view("admin.utilities.system.php-compat.assessment.all-versions-details", $templateData);
        return new \WHMCS\Http\Message\JsonResponse(array("allVersionsHtml" => $body, "lastScanned" => $iterator->getLastScanTime()));
    }
    protected function getVersionDataWithAccordionCompat(\WHMCS\Environment\Ioncube\Contracts\InspectorIteratorInterface $inspector)
    {
        $data = array();
        $loader = new \WHMCS\Environment\Ioncube\Loader\Loader100100();
        $allWhmcsSupportedPhpVersions = $thisProductSupportedPhpVersions = array("0506" => "5.6", "0700" => "7.0", "0701" => "7.1", "0702" => "7.2", "0703" => "7.3");
        foreach ($allWhmcsSupportedPhpVersions as $versionId => $version) {
            $filesThatShouldBeLookedAt = new \WHMCS\Environment\Ioncube\Inspector\Filter\AnyEncodingIterator($version, $inspector);
            $data[$version] = new View\AccordionByCompat\VersionDetails($version, $versionId, $filesThatShouldBeLookedAt, $loader, in_array($version, $thisProductSupportedPhpVersions));
        }
        return $data;
    }
}

?>