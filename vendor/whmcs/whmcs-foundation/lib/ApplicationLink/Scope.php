<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\ApplicationLink;

class Scope extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbloauthserver_scopes";
    protected $scopePivotTable = "tbloauthserver_client_scopes";
    protected $standardScopes = array(array("scope" => "clientarea:sso", "description" => "Single Sign-on for Client Area", "isDefault" => 1), array("scope" => "clientarea:profile", "description" => "Account Profile", "isDefault" => 0), array("scope" => "clientarea:billing_info", "description" => "Manage Billing Information", "isDefault" => 0), array("scope" => "clientarea:emails", "description" => "Email History", "isDefault" => 0), array("scope" => "clientarea:announcements", "description" => "Announcements", "isDefault" => 0), array("scope" => "clientarea:downloads", "description" => "Downloads", "isDefault" => 0), array("scope" => "clientarea:knowledgebase", "description" => "Knowledgebase", "isDefault" => 0), array("scope" => "clientarea:network_status", "description" => "Network Status", "isDefault" => 0), array("scope" => "clientarea:services", "description" => "Products/Services", "isDefault" => 0), array("scope" => "clientarea:product_details", "description" => "Product Info/Details (requires associated serviceId)", "isDefault" => 0), array("scope" => "clientarea:domains", "description" => "Domains", "isDefault" => 0), array("scope" => "clientarea:domain_details", "description" => "Domain Info/Details (requires associated domainId)", "isDefault" => 0), array("scope" => "clientarea:invoices", "description" => "Invoices", "isDefault" => 0), array("scope" => "clientarea:tickets", "description" => "Support Tickets", "isDefault" => 0), array("scope" => "clientarea:submit_ticket", "description" => "Submit New Ticket", "isDefault" => 0), array("scope" => "clientarea:shopping_cart", "description" => "Shopping Cart Default Product Group", "isDefault" => 0), array("scope" => "clientarea:upgrade", "description" => "Upgrade/Downgrade", "isDefault" => 0), array("scope" => "clientarea:shopping_cart_domain_register", "description" => "Shopping Cart Register New Domain", "isDefault" => 0), array("scope" => "clientarea:shopping_cart_domain_transfer", "description" => "Shopping Cart Transfer Existing Domain", "isDefault" => 0), array("scope" => "openid", "description" => "Scope required for OpenID Connect ID tokens", "isDefault" => 0), array("scope" => "email", "description" => "Scope used for Email Claim", "isDefault" => 0), array("scope" => "profile", "description" => "Scope used for Profile Claim", "isDefault" => 0));
    public function createTable($drop = false)
    {
        $schemaBuilder = \Illuminate\Database\Capsule\Manager::schema();
        if ($drop) {
            $schemaBuilder->dropIfExists($this->getTable());
        }
        if (!$schemaBuilder->hasTable($this->getTable())) {
            $schemaBuilder->create($this->getTable(), function ($table) {
                $table->increments("id");
                $table->string("scope", 80)->unique();
                $table->string("description")->default("");
                $table->tinyInteger("is_default")->default(0);
                $table->timestamp("created_at")->default("0000-00-00 00:00:00");
                $table->timestamp("updated_at")->default("0000-00-00 00:00:00");
            });
            foreach ($this->getStandardScopes() as $standardScope) {
                $scope = new static();
                foreach ($standardScope as $attribute => $value) {
                    $scope->{$attribute} = $value;
                }
                $scope->save();
            }
        }
    }
    public function getStandardScopes()
    {
        return $this->standardScopes;
    }
}

?>