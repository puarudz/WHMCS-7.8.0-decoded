<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Apps\Meta\Schema\Version1;

class Local extends \WHMCS\Apps\Meta\Schema\AbstractVersion
{
    public function getType()
    {
        return $this->meta("type");
    }
    public function getName()
    {
        return $this->meta("name");
    }
    public function getVersion()
    {
        return $this->meta("version");
    }
    public function getLicense()
    {
        return $this->meta("license");
    }
    public function getCategory()
    {
        return $this->meta("category");
    }
    public function getDisplayName()
    {
        return $this->meta("description.name");
    }
    public function getTagline()
    {
        return $this->meta("description.tagline");
    }
    public function getShortDescription()
    {
        return $this->meta("description.short");
    }
    public function getLongDescription()
    {
        return $this->meta("description.long");
    }
    public function getFeatures()
    {
        return $this->meta("description.features");
    }
    public function getLogoFilename()
    {
        return $this->meta("logo.filename");
    }
    public function getLogoBase64()
    {
        return $this->meta("logo.base64");
    }
    public function getMarketplaceUrl()
    {
        return $this->meta("marketplace.url");
    }
    public function getAuthors()
    {
        return $this->meta("authors");
    }
    public function getHomepageUrl()
    {
        return $this->meta("support.homepage");
    }
    public function getLearnMoreUrl()
    {
        return $this->meta("support.learn_more");
    }
    public function getSupportEmail()
    {
        return $this->meta("support.email");
    }
    public function getSupportUrl()
    {
        return $this->meta("support.support_url");
    }
    public function getDocumentationUrl()
    {
        return $this->meta("support.docs_url");
    }
}

?>