<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\ApplicationSupport\Route\Middleware;

class DirectoryValidation implements \WHMCS\Route\Contracts\Middleware\StrategyInterface
{
    use \WHMCS\Route\Middleware\Strategy\DelegatingMiddlewareTrait;
    public function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        $this->assertAdminDirectory($request);
        return $request;
    }
    public function assertAdminDirectory(\WHMCS\Http\Message\ServerRequest $request)
    {
        $isAdminRequest = $request->isAdminRequest();
        $hasDefaultAdminDir = $this->hasDefaultAdminDirectoryOnDisk();
        $hasCustomAdminPath = \WHMCS\Admin\AdminServiceProvider::hasConfiguredCustomAdminPath();
        $adminDirErrorMsg = "";
        if ($hasCustomAdminPath && !$isAdminRequest) {
            $adminDirErrorMsg = $this->messageAccessingAdminFileOutsideOfCustomAdminPath();
        } else {
            if ($hasCustomAdminPath && $hasDefaultAdminDir) {
                $adminDirErrorMsg = $this->messageForgetAboutDefaultAdminDir();
            } else {
                if (!$isAdminRequest) {
                    $adminDirErrorMsg = $this->messageForgetToConfigureCustomAdminPath();
                }
            }
        }
        if ($adminDirErrorMsg) {
            throw new \WHMCS\Exception\Fatal($adminDirErrorMsg);
        }
        return null;
    }
    public function hasDefaultAdminDirectoryOnDisk()
    {
        return \WHMCS\Admin\AdminServiceProvider::hasDefaultAdminDirectory();
    }
    protected function messageForgetAboutDefaultAdminDir()
    {
        return "You are attempting to access the admin area via a custom" . " directory, but we have detected the presence of a default" . " \"admin\" directory too. This could indicate files from a recent" . " update have been uploaded to the default admin path location" . " instead of the custom one, resulting in these files being out of" . " date. Please ensure your custom admin folder contains all the" . " latest files, and delete the default admin directory to continue.";
    }
    protected function messageForgetToConfigureCustomAdminPath()
    {
        return "You are attempting to access the admin area via a directory" . " that is not configured. Please either revert to the default" . " admin directory name, or see our documentation for" . " <a href=\"https://docs.whmcs.com/Customising_the_Admin_Directory\"" . " target=\"_blank\">Customising the Admin Directory</a>.";
    }
    protected function messageAccessingAdminFileOutsideOfCustomAdminPath()
    {
        return "You are attempting to access the admin area via a" . " directory that is different from the one configured. Please refer" . " to the <a href=\"https://docs.whmcs.com/Customising_the_Admin_Directory\"" . " target=\"_blank\">Customising the Admin Directory</a>" . " documentation for instructions on how to update it.";
    }
}

?>