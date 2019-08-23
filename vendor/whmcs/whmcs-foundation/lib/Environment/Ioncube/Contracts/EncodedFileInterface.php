<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Environment\Ioncube\Contracts;

interface EncodedFileInterface
{
    const ENCODER_VERSION_OUTDATED = "outdated";
    const ENCODER_VERSION_V8_OR_OLDER = "8minus";
    const ENCODER_VERSION_V9_PLUS_NON_BUNDLED = "9plus";
    const ENCODER_VERSION_V10_PLUS_NON_BUNDLED = "10nonbundled";
    const ENCODER_VERSION_V10_PLUS_BUNDLED = "10bundled";
    const ENCODER_VERSION_UNKNOWN = "unknown";
    const ENCODER_VERSION_NONE = "none";
    const ASSESSMENT_COMPAT_NO = 0;
    const ASSESSMENT_COMPAT_YES = 1;
    const ASSESSMENT_COMPAT_LIKELY = 2;
    const ASSESSMENT_COMPAT_UNLIKELY = 3;
    public function getFilename();
    public function getFileContentHash();
    public function getEncoderVersion();
    public function getTargetPhpVersion();
    public function versionCompatibilityAssessment($phpVersion, LoaderInterface $loader);
    public function canRunOnPhpVersion($phpVersion);
}

?>