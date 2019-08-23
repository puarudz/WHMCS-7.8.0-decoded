<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Download\View;

class Index extends \WHMCS\ClientArea
{
    protected function initializeView()
    {
        \Menu::addContext("routeNamespace", "download");
        parent::initializeView();
        $this->setPageTitle(\Lang::trans("downloadstitle"));
        $this->setDisplayTitle(\Lang::trans("downloadstitle"));
        $this->setTagLine(\Lang::trans("downdoadsdesc"));
        $this->addToBreadCrumb(\WHMCS\Config\Setting::getValue("SystemURL"), \Lang::trans("globalsystemname"))->addToBreadCrumb(routePath("download-index"), \Lang::trans("downloadstitle"));
        $this->addOutputHookFunction("ClientAreaPageDownloads");
        \Menu::primarySidebar("downloadList");
        \Menu::secondarySidebar("downloadList");
        $this->setTemplate("downloads");
    }
    private function formatFileSize($val, $digits = 3)
    {
        $factor = 1024;
        $symbols = array("", "k", "M", "G", "T", "P", "E", "Z", "Y");
        for ($i = 0; $i < count($symbols) - 1 && $factor <= $val; $i++) {
            $val /= $factor;
        }
        $p = strpos($val, ".");
        if ($p !== false && $digits < $p) {
            $val = round($val);
        } else {
            if ($p !== false) {
                $val = round($val, $digits - $p);
            }
        }
        return round($val, $digits) . " " . $symbols[$i] . "B";
    }
    public function formatDownloadsForTemplate($downloads)
    {
        $downloadsFilesystem = \Storage::downloads();
        $systemUrl = \App::getSystemURL();
        $result = array();
        foreach ($downloads as $download) {
            $id = $download->id;
            $type = $download->type;
            $title = $download->title;
            $description = $download->description;
            $filename = $download->fileLocation;
            $numdownloads = $download->timesDownloaded;
            $clientsonly = $download->clientDownloadOnly;
            try {
                $filesize = $this->formatFileSize($downloadsFilesystem->getSize($filename));
            } catch (\Exception $e) {
                $filesize = \Lang::trans("na");
            }
            $filenameArr = explode(".", $filename);
            $fileext = end($filenameArr);
            if ($fileext == "doc") {
                $type = "doc";
            }
            if ($fileext == "gif" || $fileext == "jpg" || $fileext == "jpeg" || $fileext == "png") {
                $type = "picture";
            }
            if ($fileext == "txt") {
                $type = "txt";
            }
            if ($fileext == "zip") {
                $type = "zip";
            }
            $type = \DI::make("asset")->imgTag($type . ".png", "File", array("align" => "absmiddle"));
            $result[] = array("type" => $type, "title" => $title, "urlfriendlytitle" => getModRewriteFriendlyString($title), "description" => $description, "downloads" => $numdownloads, "filesize" => $filesize, "clientsonly" => $clientsonly, "link" => $systemUrl . "dl.php?type=d&amp;id=" . $id);
        }
        return $result;
    }
}

?>