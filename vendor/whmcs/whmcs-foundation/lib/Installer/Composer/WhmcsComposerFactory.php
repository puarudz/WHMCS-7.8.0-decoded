<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Installer\Composer;

class WhmcsComposerFactory extends \Composer\Factory
{
    protected function addLocalRepository(\Composer\IO\IOInterface $io, \Composer\Repository\RepositoryManager $rm, $vendorDir)
    {
        $rm->setRepositoryClass(WhmcsRepository::REPOSITORY_TYPE, "WHMCS\\Installer\\Composer\\WhmcsRepository");
        parent::addLocalRepository($io, $rm, $vendorDir);
    }
}

?>