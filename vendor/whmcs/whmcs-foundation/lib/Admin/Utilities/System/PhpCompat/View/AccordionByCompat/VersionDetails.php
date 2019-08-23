<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Utilities\System\PhpCompat\View\AccordionByCompat;

class VersionDetails extends \WHMCS\Admin\Utilities\System\PhpCompat\View\AbstractVersionDetails
{
    protected $assessmentGroups = array();
    public function __construct($phpVersion, $phpVersionId, $iterator, $ioncubeLoader, $whmcsCompat, $style = NULL)
    {
        parent::__construct($phpVersion, $phpVersionId, $iterator, $ioncubeLoader, $whmcsCompat);
        if (!$style) {
            $style = new Style\ThreeAssessmentGroup();
        }
        $this->assessmentGroups = $style->defaultAssessmentGroups($phpVersionId);
    }
    public function getHtml()
    {
        return $this->getAccordionHtml();
    }
    protected function getAssessmentViewGroups()
    {
        $rootDirLength = strlen(ROOTDIR) + 1;
        $assessmentGroups = $this->assessmentGroups;
        foreach ($this->getIterator() as $item) {
            $assessment = $item->versionCompatibilityAssessment($this->getPhpVersion(), $this->getIoncubeLoader());
            if ($assessment == \WHMCS\Environment\Ioncube\Contracts\EncodedFileInterface::ASSESSMENT_COMPAT_LIKELY) {
                $assessment = \WHMCS\Environment\Ioncube\Contracts\EncodedFileInterface::ASSESSMENT_COMPAT_UNLIKELY;
            }
            $filename = $item->getFilename();
            if (strpos($filename, ROOTDIR) === 0) {
                $filename = substr($filename, $rootDirLength);
            }
            $fileData = "<tr><td>" . $filename . "</td></tr>";
            $assessmentGroups[$assessment]["data"][] = $fileData;
        }
        $viewReadyGroups = array();
        foreach ($assessmentGroups as $assessment => $group) {
            $viewReadyGroups[$assessment] = (new AccordionGroup())->setId($group["type"])->setSubId($this->getPhpVersionId())->setDescription($group["desc"])->setTitle($group["title"])->setTitleCssClass($group["titleCssClass"])->setData($group["data"]);
        }
        return $viewReadyGroups;
    }
    protected function getAccordionHtml()
    {
        $assessmentGroups = $this->getAssessmentViewGroups();
        return view("admin.utilities.system.php-compat.assessment.version-detail-accordions", array("phpVersionId" => $this->getPhpVersionId(), "assessmentGroups" => $assessmentGroups));
    }
}

?>