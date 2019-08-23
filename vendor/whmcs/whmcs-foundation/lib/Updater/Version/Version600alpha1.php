<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version600alpha1 extends IncrementalVersion
{
    protected $updateActions = array("removeDuplicateSettings", "convertMailTemplateBooleanColumns", "convertClientUnixTimestampColumns", "convertClientBooleanColumns", "convertDomainBooleanColumns", "convertProductBooleanColumns", "convertProductGroupBooleanColumns", "convertDownloadBooleanColumns", "convertDownloadCategoryBooleanColumns", "migrateProductDownloadIdsToItsTable", "migrateProductUpgradeIdsToItsTable", "convertServiceBooleanColumns", "convertAnnouncementBooleanColumns", "updateAdminUserForAutoReleaseModule", "createServiceUnsuspendedEmailTemplate", "addManualUpgradeRequiredEmailTemplate", "convertNoMD5Passwords", "populateTopLevelDomainsAndCategories", "migrateDiscontinuedOrderFormTemplates", "migrateDiscontinuedAdminOriginalTemplate", "convertContactUnixTimestampColumns");
    public function __construct(\WHMCS\Version\SemanticVersion $version)
    {
        parent::__construct($version);
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "WHMCS" . DIRECTORY_SEPARATOR . "Email";
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "Smarty";
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "WHMCS" . DIRECTORY_SEPARATOR . "Smarty" . DIRECTORY_SEPARATOR . "Compiler.php";
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "phpseclib";
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "ircmaxell";
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "dbconnect.php";
    }
    protected function removeDuplicateSettings()
    {
        $distinctSettingNames = \Illuminate\Database\Capsule\Manager::table("tblconfiguration")->select("setting", "value")->distinct("setting")->get();
        foreach ($distinctSettingNames as $distinctSetting) {
            $settings = \Illuminate\Database\Capsule\Manager::table("tblconfiguration")->where("setting", "=", $distinctSetting->setting)->get();
            for ($i = 0; $i < count($settings) - 1; $i++) {
                \Illuminate\Database\Capsule\Manager::table("tblconfiguration")->where("setting", "=", $distinctSetting->setting)->where("value", "=", $settings[$i]->value)->delete();
                if (\Illuminate\Database\Capsule\Manager::table("tblconfiguration")->where("setting", "=", $distinctSetting->setting)->count() == 0) {
                    \Illuminate\Database\Capsule\Manager::table("tblconfiguration")->insert(array("setting" => $distinctSetting->setting, "value" => $settings[$i]->value, "created_at" => $settings[$i]->created_at, "updated_at" => $settings[$i]->updated_at));
                    break;
                }
            }
        }
        return $this;
    }
    protected function convertMailTemplateBooleanColumns()
    {
        $columns = array("disabled", "custom", "plaintext");
        foreach ($columns as $column) {
            \WHMCS\Mail\Template::convertBooleanColumn($column);
        }
        return $this;
    }
    protected function convertClientUnixTimestampColumns()
    {
        $columns = array("pwresetexpiry");
        foreach ($columns as $column) {
            \WHMCS\User\Client::convertUnixTimestampIntegerToTimestampColumn($column);
        }
        return $this;
    }
    protected function convertClientBooleanColumns()
    {
        $columns = array("taxexempt", "latefeeoveride", "overideduenotices", "separateinvoices", "disableautocc");
        foreach ($columns as $column) {
            \WHMCS\User\Client::convertBooleanColumn($column);
        }
        return $this;
    }
    protected function convertDomainBooleanColumns()
    {
        $columns = array("dnsmanagement", "emailforwarding", "idprotection", "donotrenew", "synced");
        foreach ($columns as $column) {
            \WHMCS\Domain\Domain::convertBooleanColumn($column);
        }
        return $this;
    }
    protected function convertProductBooleanColumns()
    {
        $columns = array("hidden", "showdomainoptions", "stockcontrol", "proratabilling", "configoptionsupgrade", "tax", "affiliateonetime", "retired");
        foreach ($columns as $column) {
            \WHMCS\Product\Product::convertBooleanColumn($column);
        }
        return $this;
    }
    protected function convertProductGroupBooleanColumns()
    {
        $columns = array("hidden");
        foreach ($columns as $column) {
            \WHMCS\Product\Group::convertBooleanColumn($column);
        }
        return $this;
    }
    protected function convertDownloadBooleanColumns()
    {
        $columns = array("clientsonly", "hidden", "productdownload");
        foreach ($columns as $column) {
            \WHMCS\Download\Download::convertBooleanColumn($column);
        }
        return $this;
    }
    protected function convertDownloadCategoryBooleanColumns()
    {
        $columns = array("hidden");
        foreach ($columns as $column) {
            \WHMCS\Download\Category::convertBooleanColumn($column);
        }
        return $this;
    }
    protected function migrateProductDownloadIdsToItsTable()
    {
        $productModel = new \WHMCS\Product\Product();
        if (\Illuminate\Database\Capsule\Manager::schema()->hasColumn($productModel->getTable(), "downloads")) {
            $productsWithDownloads = \WHMCS\Product\Product::where("downloads", "!=", "")->where("downloads", "!=", "N;")->get();
            foreach ($productsWithDownloads as $product) {
                $downloads = safe_unserialize($product->downloads);
                if (!is_array($downloads)) {
                    continue;
                }
                foreach ($downloads as $downloadId) {
                    \Illuminate\Database\Capsule\Manager::table("tblproduct_downloads")->insert(array("product_id" => $product->id, "download_id" => $downloadId));
                }
            }
            \Illuminate\Database\Capsule\Manager::schema()->table($productModel->getTable(), function ($table) {
                $table->dropColumn("downloads");
            });
        }
        return $this;
    }
    protected function migrateProductUpgradeIdsToItsTable()
    {
        $productModel = new \WHMCS\Product\Product();
        if (\Illuminate\Database\Capsule\Manager::schema()->hasColumn($productModel->getTable(), "upgradepackages")) {
            $productsWithUpgrades = \WHMCS\Product\Product::where("upgradepackages", "!=", "")->where("upgradepackages", "!=", "N;")->get();
            foreach ($productsWithUpgrades as $product) {
                $upgrades = safe_unserialize($product->upgradepackages);
                if (!is_array($upgrades)) {
                    continue;
                }
                foreach ($upgrades as $upgradeProductId) {
                    \Illuminate\Database\Capsule\Manager::table("tblproduct_upgrade_products")->insert(array("product_id" => $product->id, "upgrade_product_id" => $upgradeProductId));
                }
            }
            \Illuminate\Database\Capsule\Manager::schema()->table($productModel->getTable(), function ($table) {
                $table->dropColumn("upgradepackages");
            });
        }
        return $this;
    }
    protected function convertServiceBooleanColumns()
    {
        $columns = array("overideautosuspend");
        foreach ($columns as $column) {
            \WHMCS\Service\Service::convertBooleanColumn($column);
        }
        return $this;
    }
    protected function convertAnnouncementBooleanColumns()
    {
        $columns = array("published");
        foreach ($columns as $column) {
            \WHMCS\Announcement\Announcement::convertBooleanColumn($column);
        }
        return $this;
    }
    protected function updateAdminUserForAutoReleaseModule()
    {
        $admin = \WHMCS\User\Admin::where("disabled", "=", false)->first(array("id", "username", "firstname", "lastname"));
        $adminToSave = (string) $admin->id . "|" . $admin->firstname . " " . $admin->lastname . " (" . $admin->username . ")";
        $products = \Illuminate\Database\Capsule\Manager::table("tblproducts")->where("servertype", "=", "autorelease")->get();
        foreach ($products as $product) {
            \Illuminate\Database\Capsule\Manager::table("tblproducts")->where("id", "=", $product->id)->update(array("configoption7" => $adminToSave));
        }
        return $this;
    }
    protected function createServiceUnsuspendedEmailTemplate()
    {
        $message = "<p>Dear {\$client_name},</p>" . PHP_EOL . "<p>This is a notification that your service has now been unsuspended." . " The details of this unsuspension are below:</p>" . PHP_EOL . "<p>Product/Service: {\$service_product_name}<br />" . "{if \$service_domain}Domain: {\$service_domain}<br />" . "{/if}Amount: {\$service_recurring_amount}<br />" . "Due Date: {\$service_next_due_date}</p>" . PHP_EOL . "<p>{\$signature}</p>";
        $template = new \WHMCS\Mail\Template();
        $template->type = "product";
        $template->name = "Service Unsuspension Notification";
        $template->subject = "Service Unsuspension Notification";
        $template->message = $message;
        $template->save();
        return $this;
    }
    protected function addManualUpgradeRequiredEmailTemplate()
    {
        $existingEmail = \WHMCS\Mail\Template::where("name", "=", "Manual Upgrade Required")->count();
        if (!$existingEmail) {
            $emailMessage = "<p>An upgrade order has received its payment, " . "but does not support automatic upgrades and requires manually processing.</p>" . PHP_EOL . "<p>Client ID: {\$client_id}<br />Service ID: {\$service_id}<br />Order ID: {\$order_id}</p>" . PHP_EOL . "<p>{if \$upgrade_type eq 'package'}New Package ID: {\$new_package_id}<br />" . "Existing Billing Cycle: {\$billing_cycle}<br />New Billing Cycle: {\$new_billing_cycle}" . "{else}Configurable Option: {\$config_id}<br />Option Type: {\$option_type}<br />" . "Current Value: {\$current_value}<br />New Value: {\$new_value}{/if}</p>" . PHP_EOL . "<p><a href=\"{\$whmcs_admin_url}orders.php?action=view&id={\$order_id}\">" . PHP_EOL . "{\$whmcs_admin_url}orders.php?action=view&id={\$order_id}</a></p>";
            $email = new \WHMCS\Mail\Template();
            $email->name = "Manual Upgrade Required";
            $email->subject = "Manual Upgrade Required";
            $email->message = $emailMessage;
            $email->type = "admin";
            $email->custom = false;
            $email->plaintext = false;
            $email->save();
        }
        return $this;
    }
    protected function convertNoMD5Passwords()
    {
        $nomd5 = \WHMCS\Config\Setting::getValue("NOMD5");
        if (!empty($nomd5)) {
            require_once ROOTDIR . "/includes/functions.php";
            require_once ROOTDIR . "/includes/clientfunctions.php";
            foreach (\WHMCS\User\Client::all() as $client) {
                $client->password = generateClientPW(decrypt($client->password));
                $client->save();
            }
            $contacts = \Illuminate\Database\Capsule\Manager::table("tblcontacts")->get();
            foreach ($contacts as $contact) {
                $password = generateClientPW(decrypt($contact->password));
                \Illuminate\Database\Capsule\Manager::table("tblcontacts")->where("id", "=", $contact->id)->update(array("password" => $password));
            }
            try {
                \WHMCS\Config\Setting::findOrFail("NOMD5")->delete();
            } catch (\Exception $e) {
            }
        }
        return $this;
    }
    protected function populateTopLevelDomainsAndCategories()
    {
        $rawTopLevelDomainsAndCategories = "{\"aaa.pro\":[\"gTLD\",\"Specialty\",\"Sponsored\"],\"ab.ca\":[\"ccTLD\"],\"abogado\":[\"Services\"],\"abudhabi\":[\"Geographic\"],\"ac\":[\"ccTLD\",\"Geography\"],\"aca.pro\":[\"gTLD\",\"Specialty\",\"Sponsored\"],\"academy\":[\"Education\",\"Popular\",\"gTLD\"],\"accountant\":[\"Money and Finance\",\"Services\"],\"accountants\":[\"Featured\",\"gTLD\",\"Money and Finance\",\"Services\"],\"acct.pro\":[\"gTLD\",\"Specialty\",\"Sponsored\"],\"aco\":[\"Community\"],\"active\":[\"Identity and Lifestyle\"],\"actor\":[\"Arts and Entertainment\",\"Featured\",\"gTLD\",\"Identity and Lifestyle\"],\"adac\":[\"Community\"],\"ads\":[\"Business\"],\"adult\":[\"Adult\"],\"ae.org\":[\"ccTLD\"],\"africa\":[\"Geographic\"],\"ag\":[\"ccTLD\"],\"agency\":[\"Business\",\"Popular\",\"gTLD\"],\"ah.cn\":[\"ccTLD\"],\"airforce\":[\"Featured\",\"Identity and Lifestyle\"],\"alsace\":[\"Geographic\"],\"am\":[\"ccTLD\",\"Geography\",\"Specialty\"],\"amsterdam\":[\"Geographic\"],\"analytics\":[\"Business\"],\"and\":[\"Novelty\"],\"apartments\":[\"Featured\",\"Real Estate\",\"Services\"],\"app\":[\"Featured\",\"Services\",\"Technology\"],\"aquitaine\":[\"Geographic\"],\"ar.com\":[\"ccTLD\",\"Other\"],\"arab\":[\"Identity and Lifestyle\"],\"archi\":[\"Popular\",\"gTLD\",\"Services\"],\"architect\":[\"Featured\",\"Services\"],\"are\":[\"Novelty\"],\"army\":[\"Featured\",\"Identity and Lifestyle\"],\"art\":[\"Arts and Entertainment\",\"Community\",\"Featured\"],\"asia\":[\"ccTLD\",\"Popular\",\"Geography\",\"gTLD\"],\"associates\":[\"Business\",\"Featured\",\"Popular\",\"gTLD\"],\"at\":[\"ccTLD\",\"Geography\"],\"attorney\":[\"Featured\",\"Services\"],\"au\":[\"ccTLD\"],\"auction\":[\"Featured\",\"Shopping\"],\"audi\":[\"Community\"],\"audible\":[\"Arts and Entertainment\"],\"audio\":[\"Arts and Entertainment\",\"Featured\",\"Shopping\"],\"auto\":[\"Featured\",\"Services\",\"Shopping\"],\"autos\":[\"Services\",\"Shopping\"],\"avocat.pro\":[\"gTLD\",\"Specialty\",\"Sponsored\"],\"baby\":[\"Featured\",\"Identity and Lifestyle\"],\"band\":[\"Arts and Entertainment\",\"Featured\",\"Interest\"],\"bank\":[\"Community\",\"Money and Finance\",\"Services\"],\"banque\":[\"Money and Finance\",\"Services\"],\"bar\":[\"Featured\",\"Food and Drink\",\"Popular\",\"Geographic\",\"gTLD\"],\"bar.pro\":[\"gTLD\",\"Specialty\",\"Sponsored\"],\"barcelona\":[\"Community\",\"Geographic\"],\"bargains\":[\"Popular\",\"gTLD\",\"Shopping\"],\"baseball\":[\"Featured\",\"Interest\",\"Sports\"],\"basketball\":[\"Featured\",\"Interest\",\"Sports\"],\"bauhaus\":[\"Business\"],\"bayern\":[\"Geographic\"],\"bbb\":[\"Community\"],\"bc.ca\":[\"ccTLD\"],\"be\":[\"ccTLD\",\"Geography\"],\"beauty\":[\"Featured\",\"Identity and Lifestyle\"],\"beer\":[\"Food and Drink\"],\"beknown\":[\"Novelty\"],\"berlin\":[\"Community\",\"Geographic\"],\"best\":[\"Popular\",\"gTLD\",\"Novelty\"],\"bet\":[\"Featured\",\"Leisure and Recreation\"],\"bible\":[\"Identity and Lifestyle\"],\"bid\":[\"Popular\",\"gTLD\",\"Leisure and Recreation\",\"Shopping\"],\"bike\":[\"Popular\",\"gTLD\",\"Shopping\"],\"bingo\":[\"Featured\",\"Leisure and Recreation\"],\"bio\":[\"Identity and Lifestyle\"],\"biz\":[\"Popular\",\"gTLD\"],\"bj.cn\":[\"ccTLD\"],\"black\":[\"Identity and Lifestyle\",\"Novelty\"],\"blackfriday\":[\"Popular\",\"gTLD\",\"Shopping\"],\"blog\":[\"Featured\",\"Interest\",\"Technology\"],\"blue\":[\"Popular\",\"gTLD\",\"Novelty\"],\"boats\":[\"Featured\",\"Shopping\"],\"bond\":[\"Money and Finance\"],\"boo\":[\"Novelty\"],\"book\":[\"Arts and Entertainment\",\"Featured\",\"Interest\"],\"booking\":[\"Business\"],\"boston\":[\"Geographic\"],\"boutique\":[\"Popular\",\"gTLD\",\"Shopping\"],\"box\":[\"Novelty\"],\"br.com\":[\"ccTLD\",\"Geography\"],\"broadway\":[\"Arts and Entertainment\",\"Featured\",\"Geographic\",\"Interest\"],\"broker\":[\"Featured\",\"Money and Finance\",\"Services\"],\"brother\":[\"Identity and Lifestyle\"],\"brussels\":[\"Geographic\"],\"budapest\":[\"Geographic\"],\"bugatti\":[\"Community\"],\"build\":[\"Featured\",\"Popular\",\"gTLD\",\"Shopping\"],\"builders\":[\"Popular\",\"gTLD\",\"Real Estate\",\"Services\"],\"business\":[\"Business\",\"Featured\"],\"buy\":[\"Featured\",\"Shopping\"],\"buzz\":[\"Popular\",\"gTLD\",\"Identity and Lifestyle\"],\"bway\":[\"Arts and Entertainment\"],\"bz\":[\"ccTLD\",\"Geography\"],\"bzh\":[\"Community\",\"Geographic\"],\"ca\":[\"ccTLD\",\"Geography\"],\"cab\":[\"Popular\",\"gTLD\",\"Services\"],\"cafe\":[\"Featured\",\"Food and Drink\"],\"cam\":[\"Featured\",\"Interest\"],\"camera\":[\"Popular\",\"gTLD\",\"Interest\"],\"camp\":[\"Popular\",\"gTLD\",\"Interest\",\"Leisure and Recreation\"],\"capetown\":[\"Geographic\"],\"capital\":[\"Featured\",\"Popular\",\"gTLD\",\"Money and Finance\"],\"car\":[\"Interest\"],\"cards\":[\"Featured\",\"Popular\",\"gTLD\",\"Interest\"],\"care\":[\"Featured\",\"gTLD\",\"Interest\"],\"career\":[\"Business\",\"Services\"],\"careers\":[\"Business\",\"Popular\",\"gTLD\",\"Services\"],\"cars\":[\"Featured\",\"Shopping\"],\"casa\":[\"Featured\",\"Real Estate\"],\"cash\":[\"Featured\",\"gTLD\",\"Money and Finance\"],\"casino\":[\"Featured\",\"Leisure and Recreation\"],\"catalonia\":[\"Geographic\"],\"catering\":[\"Featured\",\"Food and Drink\",\"Popular\",\"gTLD\",\"Services\"],\"catholic\":[\"Community\"],\"cc\":[\"ccTLD\",\"Popular\",\"Specialty\"],\"center\":[\"Business\",\"Popular\",\"gTLD\"],\"ceo\":[\"Popular\",\"gTLD\",\"Identity and Lifestyle\"],\"cfd\":[\"Money and Finance\"],\"ch\":[\"ccTLD\",\"Geography\"],\"charity\":[\"Featured\",\"Interest\"],\"chat\":[\"Featured\",\"Technology\"],\"cheap\":[\"Popular\",\"gTLD\",\"Shopping\"],\"chesapeake\":[\"Geographic\"],\"chk\":[\"Novelty\"],\"christmas\":[\"Popular\",\"gTLD\",\"Interest\"],\"church\":[\"Featured\",\"gTLD\",\"Identity and Lifestyle\"],\"city\":[\"Featured\",\"Geographic\"],\"cityeats\":[\"Food and Drink\",\"Services\"],\"claims\":[\"Featured\",\"gTLD\",\"Services\"],\"cleaning\":[\"Featured\",\"Popular\",\"gTLD\",\"Services\"],\"click\":[\"Technology\"],\"clinic\":[\"Featured\",\"gTLD\",\"Services\"],\"clothing\":[\"Popular\",\"gTLD\",\"Shopping\"],\"cloud\":[\"Featured\",\"Technology\"],\"club\":[\"Popular\",\"gTLD\",\"Identity and Lifestyle\"],\"cm\":[\"ccTLD\",\"Geography\",\"Specialty\"],\"cn\":[\"ccTLD\",\"Geography\"],\"cn.com\":[\"ccTLD\",\"Geography\"],\"co\":[\"ccTLD\",\"Geography\",\"Specialty\"],\"co.com\":[\"Business\",\"Popular\",\"gTLD\"],\"co.in\":[\"ccTLD\"],\"co.nz\":[\"ccTLD\",\"Geography\"],\"co.uk\":[\"ccTLD\",\"Geography\"],\"coach\":[\"Featured\",\"Sports\"],\"codes\":[\"Popular\",\"gTLD\",\"Technology\"],\"coffee\":[\"Food and Drink\",\"Popular\",\"gTLD\"],\"college\":[\"Education\",\"Featured\"],\"cologne\":[\"Geographic\"],\"com\":[\"Popular\",\"gTLD\"],\"com.ag\":[\"ccTLD\"],\"com.au\":[\"ccTLD\",\"Geography\"],\"com.cn\":[\"ccTLD\",\"Other\"],\"com.co\":[\"ccTLD\",\"Other\"],\"com.de\":[\"ccTLD\",\"Other\"],\"com.es\":[\"ccTLD\",\"Geography\"],\"com.mx\":[\"ccTLD\",\"Other\"],\"com.pe\":[\"ccTLD\",\"Geography\"],\"com.pl\":[\"ccTLD\"],\"com.sc\":[\"ccTLD\"],\"com.sg\":[\"ccTLD\",\"Geography\",\"Other\"],\"com.tw\":[\"ccTLD\",\"Other\"],\"community\":[\"Featured\",\"Popular\",\"gTLD\",\"Identity and Lifestyle\"],\"company\":[\"Business\",\"Popular\",\"gTLD\"],\"compare\":[\"Shopping\"],\"computer\":[\"Popular\",\"gTLD\",\"Shopping\",\"Technology\"],\"comsec\":[\"Technology\"],\"condos\":[\"Featured\",\"Popular\",\"gTLD\",\"Real Estate\"],\"construction\":[\"Popular\",\"gTLD\",\"Services\"],\"consulting\":[\"Featured\",\"gTLD\",\"Services\"],\"contact\":[\"Identity and Lifestyle\"],\"contractors\":[\"Popular\",\"gTLD\",\"Services\"],\"cooking\":[\"Food and Drink\",\"Popular\",\"gTLD\",\"Interest\"],\"cookingchannel\":[\"Identity and Lifestyle\"],\"cool\":[\"Featured\",\"Popular\",\"gTLD\",\"Novelty\"],\"corp\":[\"Business\",\"Community\",\"Featured\"],\"corsica\":[\"Community\"],\"country\":[\"Popular\",\"Geographic\",\"gTLD\"],\"coupon\":[\"Shopping\"],\"coupons\":[\"Featured\",\"Shopping\"],\"courses\":[\"Education\"],\"cpa\":[\"Community\",\"Featured\",\"Money and Finance\",\"Services\"],\"cpa.pro\":[\"gTLD\",\"Specialty\",\"Sponsored\"],\"cq.cn\":[\"ccTLD\"],\"credit\":[\"Featured\",\"gTLD\",\"Money and Finance\"],\"creditcard\":[\"Featured\",\"gTLD\",\"Money and Finance\"],\"creditunion\":[\"Money and Finance\",\"Services\"],\"cricket\":[\"Featured\",\"Interest\",\"Sports\"],\"cruise\":[\"Leisure and Recreation\"],\"cruises\":[\"Popular\",\"gTLD\",\"Leisure and Recreation\"],\"cymru\":[\"Geographic\"],\"cyou\":[\"Novelty\"],\"dad\":[\"Identity and Lifestyle\"],\"dance\":[\"Popular\",\"gTLD\",\"Leisure and Recreation\"],\"data\":[\"Featured\",\"Technology\"],\"date\":[\"Identity and Lifestyle\"],\"dating\":[\"Featured\",\"Popular\",\"gTLD\",\"Identity and Lifestyle\"],\"day\":[\"Novelty\"],\"dds\":[\"Services\"],\"de\":[\"ccTLD\",\"Geography\"],\"de.com\":[\"ccTLD\",\"Other\"],\"deal\":[\"Shopping\"],\"deals\":[\"Featured\",\"Shopping\"],\"degree\":[\"Education\",\"Featured\"],\"delivery\":[\"Featured\",\"Services\"],\"democrat\":[\"Popular\",\"gTLD\",\"Identity and Lifestyle\"],\"dental\":[\"Featured\",\"gTLD\",\"Services\"],\"dentist\":[\"Featured\",\"Services\"],\"desi\":[\"Geographic\"],\"design\":[\"Featured\",\"Services\"],\"diamonds\":[\"Popular\",\"gTLD\",\"Shopping\"],\"diet\":[\"Featured\",\"Identity and Lifestyle\"],\"digital\":[\"Featured\",\"gTLD\",\"Technology\"],\"direct\":[\"Featured\",\"gTLD\",\"Services\"],\"directory\":[\"Popular\",\"gTLD\",\"Services\"],\"discount\":[\"Featured\",\"gTLD\",\"Shopping\"],\"diy\":[\"Interest\"],\"docs\":[\"Services\",\"Technology\"],\"doctor\":[\"Featured\",\"Services\"],\"dog\":[\"Featured\",\"Interest\"],\"doha\":[\"Geographic\"],\"domains\":[\"Popular\",\"gTLD\",\"Technology\"],\"dot\":[\"Technology\"],\"dotafrica\":[\"Geographic\"],\"download\":[\"Services\",\"Technology\"],\"dubai\":[\"Geographic\"],\"durban\":[\"Geographic\"],\"dvr\":[\"Technology\"],\"earth\":[\"Geographic\"],\"eat\":[\"Food and Drink\",\"Interest\"],\"eco\":[\"Community\",\"Featured\",\"Interest\"],\"ecom\":[\"Business\",\"Shopping\"],\"edeka\":[\"Community\"],\"education\":[\"Education\",\"Popular\",\"gTLD\"],\"email\":[\"Popular\",\"gTLD\",\"Services\",\"Technology\"],\"energy\":[\"Featured\",\"gTLD\",\"Services\"],\"eng.pro\":[\"gTLD\",\"Specialty\",\"Sponsored\"],\"engineer\":[\"Featured\",\"Services\"],\"engineering\":[\"Featured\",\"Popular\",\"gTLD\",\"Services\"],\"enterprises\":[\"Business\",\"Popular\",\"gTLD\"],\"epost\":[\"Technology\"],\"equipment\":[\"Popular\",\"gTLD\",\"Shopping\"],\"es\":[\"ccTLD\",\"Geography\"],\"esq\":[\"Services\"],\"est\":[\"Novelty\"],\"estate\":[\"Popular\",\"gTLD\",\"Money and Finance\"],\"eu\":[\"ccTLD\",\"Geography\"],\"eu.com\":[\"ccTLD\",\"Geography\"],\"eus\":[\"Community\",\"Geographic\"],\"events\":[\"Arts and Entertainment\",\"Featured\",\"Popular\",\"gTLD\",\"Leisure and Recreation\"],\"exchange\":[\"Featured\",\"Popular\",\"gTLD\",\"Money and Finance\",\"Shopping\"],\"expert\":[\"Featured\",\"Popular\",\"gTLD\",\"Services\"],\"exposed\":[\"Popular\",\"gTLD\",\"Identity and Lifestyle\"],\"express\":[\"Featured\",\"Identity and Lifestyle\",\"Services\"],\"fail\":[\"Featured\",\"gTLD\",\"Novelty\"],\"faith\":[\"Identity and Lifestyle\"],\"family\":[\"Featured\",\"Identity and Lifestyle\"],\"fan\":[\"Featured\",\"Identity and Lifestyle\",\"Sports\"],\"fans\":[\"Identity and Lifestyle\",\"Sports\"],\"farm\":[\"Business\",\"Featured\",\"Popular\",\"gTLD\",\"Services\"],\"fashion\":[\"Featured\",\"Identity and Lifestyle\",\"Shopping\"],\"feedback\":[\"Novelty\"],\"film\":[\"Arts and Entertainment\",\"Featured\"],\"final\":[\"Novelty\"],\"finance\":[\"Featured\",\"gTLD\",\"Money and Finance\",\"Services\"],\"financial\":[\"Featured\",\"gTLD\",\"Money and Finance\",\"Services\"],\"financialaid\":[\"Money and Finance\",\"Services\"],\"firm.in\":[\"ccTLD\"],\"fish\":[\"Featured\",\"Popular\",\"gTLD\",\"Leisure and Recreation\"],\"fishing\":[\"Featured\",\"Popular\",\"gTLD\",\"Leisure and Recreation\"],\"fit\":[\"Identity and Lifestyle\",\"Services\"],\"fitness\":[\"Featured\",\"gTLD\",\"Identity and Lifestyle\",\"Services\"],\"fj.cn\":[\"ccTLD\"],\"flights\":[\"Popular\",\"gTLD\",\"Leisure and Recreation\",\"Services\"],\"florist\":[\"Popular\",\"gTLD\",\"Services\"],\"flowers\":[\"Featured\",\"Services\"],\"fly\":[\"Leisure and Recreation\",\"Services\"],\"fm\":[\"ccTLD\",\"Geography\",\"Specialty\"],\"foo\":[\"Novelty\"],\"food\":[\"Featured\",\"Food and Drink\",\"Interest\"],\"football\":[\"Featured\",\"Interest\",\"Sports\"],\"forsale\":[\"Featured\",\"Real Estate\",\"Shopping\"],\"forum\":[\"Business\",\"Featured\",\"Interest\"],\"foundation\":[\"Business\",\"Popular\",\"gTLD\"],\"fr\":[\"ccTLD\",\"Geography\"],\"free\":[\"Featured\",\"Novelty\",\"Shopping\"],\"frl\":[\"Geographic\"],\"fun\":[\"Novelty\"],\"fund\":[\"Featured\",\"gTLD\",\"Money and Finance\"],\"furniture\":[\"Featured\",\"gTLD\",\"Shopping\"],\"futbol\":[\"Featured\",\"Popular\",\"gTLD\",\"Interest\",\"Sports\"],\"fyi\":[\"Featured\",\"Novelty\"],\"gal\":[\"Community\",\"Geographic\"],\"gallery\":[\"Arts and Entertainment\",\"Popular\",\"gTLD\"],\"game\":[\"Leisure and Recreation\"],\"games\":[\"Featured\",\"Leisure and Recreation\"],\"garden\":[\"Featured\",\"Interest\"],\"gay\":[\"Community\",\"Featured\",\"Identity and Lifestyle\"],\"gb.com\":[\"ccTLD\"],\"gb.net\":[\"ccTLD\"],\"gd.cn\":[\"ccTLD\"],\"gea\":[\"Community\"],\"gen.in\":[\"ccTLD\"],\"gent\":[\"Geographic\",\"Identity and Lifestyle\"],\"gift\":[\"Popular\",\"gTLD\",\"Interest\",\"Services\"],\"gifts\":[\"Featured\",\"Shopping\"],\"gives\":[\"Business\",\"Featured\",\"Interest\"],\"giving\":[\"Business\",\"Interest\"],\"glass\":[\"Popular\",\"gTLD\",\"Services\"],\"glean\":[\"Novelty\"],\"global\":[\"Featured\",\"Geographic\"],\"gmbh\":[\"Business\",\"Community\",\"Featured\"],\"gold\":[\"Featured\",\"Money and Finance\"],\"golf\":[\"Featured\",\"Interest\",\"Sports\"],\"goo\":[\"Novelty\"],\"gop\":[\"Identity and Lifestyle\"],\"gr.com\":[\"ccTLD\",\"Geography\"],\"graphics\":[\"Popular\",\"gTLD\",\"Services\",\"Technology\"],\"gratis\":[\"Featured\",\"gTLD\",\"Novelty\"],\"gree\":[\"Community\"],\"green\":[\"Featured\",\"Interest\"],\"gripe\":[\"Featured\",\"Popular\",\"gTLD\",\"Novelty\"],\"grocery\":[\"Food and Drink\",\"Shopping\"],\"group\":[\"Featured\",\"Identity and Lifestyle\",\"Interest\"],\"gs\":[\"ccTLD\",\"Geography\"],\"gs.cn\":[\"ccTLD\"],\"guide\":[\"Featured\",\"gTLD\",\"Services\"],\"guitars\":[\"Popular\",\"gTLD\",\"Interest\",\"Services\"],\"guru\":[\"Popular\",\"gTLD\",\"Shopping\"],\"gx.cn\":[\"ccTLD\"],\"gz.cn\":[\"ccTLD\"],\"ha.cn\":[\"ccTLD\"],\"hair\":[\"Identity and Lifestyle\"],\"halal\":[\"Community\",\"Identity and Lifestyle\"],\"hamburg\":[\"Community\",\"Geographic\"],\"haus\":[\"Featured\",\"gTLD\",\"Real Estate\"],\"hb.cn\":[\"ccTLD\"],\"he.cn\":[\"ccTLD\"],\"health\":[\"Featured\",\"Identity and Lifestyle\",\"Services\"],\"healthcare\":[\"Featured\",\"Services\"],\"heart\":[\"Identity and Lifestyle\"],\"help\":[\"Featured\",\"Services\"],\"helsinki\":[\"Geographic\"],\"here\":[\"Novelty\"],\"hi.cn\":[\"ccTLD\"],\"hiphop\":[\"Arts and Entertainment\"],\"hiv\":[\"Identity and Lifestyle\"],\"hk.cn\":[\"ccTLD\"],\"hl.cn\":[\"ccTLD\"],\"hn\":[\"ccTLD\"],\"hn.cn\":[\"ccTLD\"],\"hockey\":[\"Featured\",\"Interest\",\"Sports\"],\"holdings\":[\"Popular\",\"gTLD\",\"Shopping\"],\"holiday\":[\"Featured\",\"Popular\",\"gTLD\",\"Leisure and Recreation\"],\"home\":[\"Featured\",\"Real Estate\"],\"homes\":[\"Real Estate\"],\"horse\":[\"Popular\",\"gTLD\",\"Leisure and Recreation\"],\"hospital\":[\"Featured\",\"Services\"],\"host\":[\"Technology\"],\"hosting\":[\"Featured\",\"Services\",\"Technology\"],\"hot\":[\"Featured\",\"Novelty\"],\"hotel\":[\"Community\",\"Featured\",\"Leisure and Recreation\",\"Services\"],\"hotels\":[\"Leisure and Recreation\",\"Services\"],\"house\":[\"Popular\",\"gTLD\",\"Real Estate\"],\"how\":[\"Novelty\"],\"hu.com\":[\"ccTLD\",\"Geography\"],\"icu\":[\"Identity and Lifestyle\"],\"idn\":[\"Technology\"],\"idv.tw\":[\"ccTLD\",\"Other\",\"Specialty\"],\"ieee\":[\"Community\"],\"ikano\":[\"Community\"],\"immo\":[\"Community\",\"Featured\",\"Real Estate\"],\"immobilien\":[\"Featured\",\"Popular\",\"gTLD\",\"Real Estate\"],\"in\":[\"ccTLD\",\"Geography\"],\"inc\":[\"Business\",\"Community\",\"Featured\"],\"ind.in\":[\"ccTLD\"],\"indians\":[\"Identity and Lifestyle\"],\"industries\":[\"Business\",\"Featured\",\"Popular\",\"gTLD\"],\"info\":[\"Popular\",\"gTLD\"],\"info.pl\":[\"ccTLD\"],\"ing\":[\"Novelty\"],\"ink\":[\"Popular\",\"gTLD\",\"Interest\"],\"institute\":[\"Popular\",\"gTLD\",\"Services\"],\"insurance\":[\"Community\",\"Featured\",\"Services\"],\"insure\":[\"Featured\",\"gTLD\",\"Services\"],\"international\":[\"Popular\",\"Geographic\",\"gTLD\"],\"investments\":[\"Featured\",\"gTLD\",\"Money and Finance\",\"Services\"],\"io\":[\"ccTLD\",\"Geography\"],\"ira\":[\"Money and Finance\"],\"irish\":[\"Identity and Lifestyle\"],\"islam\":[\"Community\",\"Identity and Lifestyle\"],\"ismaili\":[\"Community\"],\"ist\":[\"Geographic\"],\"istanbul\":[\"Geographic\"],\"it\":[\"ccTLD\",\"Geography\"],\"jetzt\":[\"Identity and Lifestyle\"],\"jewelry\":[\"Featured\",\"Shopping\"],\"jobs\":[\"gTLD\",\"Sponsored\"],\"joburg\":[\"Geographic\"],\"jp\":[\"ccTLD\",\"Geography\"],\"jpn.com\":[\"ccTLD\",\"Other\"],\"js.cn\":[\"ccTLD\"],\"juegos\":[\"Featured\",\"Interest\"],\"jur.pro\":[\"gTLD\",\"Specialty\",\"Sponsored\"],\"justforu\":[\"Novelty\"],\"jx.cn\":[\"ccTLD\"],\"kaufen\":[\"Featured\",\"gTLD\",\"Shopping\"],\"kid\":[\"Identity and Lifestyle\"],\"kids\":[\"Community\",\"Identity and Lifestyle\"],\"kids.us\":[\"ccTLD\"],\"kim\":[\"Popular\",\"gTLD\",\"Identity and Lifestyle\",\"Novelty\"],\"kitchen\":[\"Food and Drink\",\"Popular\",\"gTLD\"],\"kiwi\":[\"Popular\",\"Geographic\",\"gTLD\",\"Identity and Lifestyle\"],\"koeln\":[\"Geographic\"],\"kosher\":[\"Identity and Lifestyle\"],\"kr.com\":[\"ccTLD\"],\"kyoto\":[\"Geographic\"],\"la\":[\"ccTLD\",\"Geography\",\"Specialty\"],\"lamborghini\":[\"Community\"],\"land\":[\"Popular\",\"gTLD\",\"Real Estate\"],\"lat\":[\"Identity and Lifestyle\"],\"latino\":[\"Identity and Lifestyle\"],\"law\":[\"Featured\",\"Services\"],\"law.pro\":[\"gTLD\",\"Specialty\",\"Sponsored\"],\"lawyer\":[\"Featured\",\"Services\"],\"lds\":[\"Community\",\"Identity and Lifestyle\"],\"lease\":[\"Featured\",\"Popular\",\"gTLD\",\"Real Estate\"],\"leclerc\":[\"Community\"],\"legal\":[\"Featured\",\"Services\"],\"lgbt\":[\"Identity and Lifestyle\"],\"li\":[\"ccTLD\",\"Geography\"],\"life\":[\"Featured\",\"gTLD\",\"Services\"],\"lifeinsurance\":[\"Services\"],\"lifestyle\":[\"Identity and Lifestyle\"],\"lighting\":[\"Popular\",\"gTLD\",\"Shopping\"],\"limited\":[\"Business\",\"Featured\",\"gTLD\"],\"limo\":[\"Popular\",\"gTLD\",\"Services\"],\"link\":[\"Popular\",\"gTLD\",\"Interest\",\"Services\"],\"live\":[\"Featured\",\"Identity and Lifestyle\"],\"living\":[\"Featured\",\"Identity and Lifestyle\"],\"llc\":[\"Business\",\"Community\",\"Featured\"],\"llp\":[\"Business\",\"Community\"],\"ln.cn\":[\"ccTLD\"],\"loan\":[\"Money and Finance\",\"Services\"],\"loans\":[\"Featured\",\"gTLD\",\"Money and Finance\",\"Services\"],\"lol\":[\"Novelty\"],\"london\":[\"Geographic\",\"gTLD\"],\"lotto\":[\"Leisure and Recreation\"],\"love\":[\"Featured\",\"Identity and Lifestyle\"],\"ltd\":[\"Business\",\"Featured\"],\"ltd.uk\":[\"ccTLD\"],\"luxe\":[\"Shopping\"],\"luxury\":[\"Popular\",\"gTLD\",\"Shopping\"],\"madrid\":[\"Community\",\"Geographic\"],\"mail\":[\"Featured\",\"Services\",\"Technology\"],\"maison\":[\"Featured\",\"Popular\",\"gTLD\",\"Real Estate\"],\"management\":[\"Business\",\"Popular\",\"gTLD\",\"Services\"],\"map\":[\"Featured\",\"Technology\"],\"market\":[\"Featured\",\"Shopping\"],\"marketing\":[\"Business\",\"Featured\",\"Popular\",\"gTLD\",\"Services\"],\"markets\":[\"Shopping\"],\"mb.ca\":[\"ccTLD\"],\"mba\":[\"Education\",\"Featured\",\"Identity and Lifestyle\"],\"me\":[\"ccTLD\",\"Geography\",\"Specialty\"],\"me.uk\":[\"ccTLD\",\"Geography\"],\"med\":[\"Community\",\"Services\"],\"med.pro\":[\"gTLD\",\"Specialty\",\"Sponsored\"],\"media\":[\"Featured\",\"Popular\",\"gTLD\",\"Technology\"],\"medical\":[\"Featured\",\"Services\"],\"meet\":[\"Interest\",\"Services\"],\"melbourne\":[\"Geographic\"],\"meme\":[\"Novelty\"],\"memorial\":[\"Featured\",\"Services\"],\"men\":[\"Novelty\"],\"menu\":[\"Popular\",\"gTLD\",\"Shopping\"],\"merck\":[\"Community\"],\"miami\":[\"Geographic\"],\"mls\":[\"Community\",\"Real Estate\",\"Services\"],\"mma\":[\"Community\"],\"mn\":[\"ccTLD\"],\"mo.cn\":[\"ccTLD\"],\"mobi\":[\"Popular\",\"gTLD\",\"Specialty\",\"Sponsored\"],\"mobile\":[\"Featured\",\"Services\",\"Technology\"],\"mobily\":[\"Technology\"],\"moda\":[\"Featured\",\"gTLD\",\"Interest\",\"Shopping\"],\"moe\":[\"Novelty\"],\"mom\":[\"Featured\",\"Identity and Lifestyle\"],\"money\":[\"Featured\",\"Money and Finance\",\"Services\"],\"mormon\":[\"Identity and Lifestyle\"],\"mortgage\":[\"Featured\",\"Money and Finance\",\"Services\"],\"moscow\":[\"Geographic\"],\"moto\":[\"Featured\",\"Interest\",\"Sports\"],\"motorcycles\":[\"Shopping\"],\"mov\":[\"Technology\"],\"movie\":[\"Arts and Entertainment\",\"Featured\",\"Interest\",\"Shopping\"],\"mozaic\":[\"Technology\"],\"ms\":[\"ccTLD\",\"Geography\"],\"msd\":[\"Technology\"],\"music\":[\"Arts and Entertainment\",\"Community\",\"Featured\",\"Interest\",\"Shopping\"],\"mutual\":[\"Money and Finance\"],\"mutualfunds\":[\"Money and Finance\",\"Services\"],\"nagoya\":[\"Popular\",\"Geographic\",\"gTLD\"],\"name\":[\"Popular\",\"gTLD\",\"Specialty\",\"Sponsored\"],\"navy\":[\"Featured\",\"Identity and Lifestyle\"],\"nb.ca\":[\"ccTLD\"],\"nba\":[\"Interest\",\"Sports\"],\"net\":[\"Popular\",\"gTLD\"],\"net.ag\":[\"ccTLD\"],\"net.au\":[\"ccTLD\",\"Geography\"],\"net.cn\":[\"ccTLD\",\"Geography\"],\"net.co\":[\"ccTLD\",\"Other\"],\"net.in\":[\"ccTLD\"],\"net.nz\":[\"ccTLD\",\"Geography\"],\"net.pe\":[\"ccTLD\",\"Geography\"],\"net.pl\":[\"ccTLD\"],\"net.sc\":[\"ccTLD\"],\"network\":[\"Featured\",\"Technology\"],\"new\":[\"Novelty\"],\"news\":[\"Arts and Entertainment\",\"Featured\",\"Services\"],\"nf.ca\":[\"ccTLD\"],\"ngo\":[\"Business\",\"Community\"],\"ninja\":[\"Featured\",\"Popular\",\"gTLD\",\"Leisure and Recreation\",\"Novelty\"],\"nl\":[\"ccTLD\",\"Geography\"],\"nl.ca\":[\"ccTLD\"],\"nm.cn\":[\"ccTLD\"],\"no.com\":[\"ccTLD\",\"Other\"],\"nom.co\":[\"ccTLD\",\"Other\"],\"nom.es\":[\"ccTLD\",\"Geography\"],\"nom.pe\":[\"ccTLD\",\"Geography\"],\"notes:\":[\"WatchList\"],\"now\":[\"Featured\",\"Novelty\"],\"nowruz\":[\"Identity and Lifestyle\"],\"nrw\":[\"Geographic\"],\"ns.ca\":[\"ccTLD\"],\"nt.ca\":[\"ccTLD\"],\"nu\":[\"ccTLD\",\"Geography\"],\"nu.ca\":[\"ccTLD\"],\"nx.cn\":[\"ccTLD\"],\"nyc\":[\"Geographic\",\"gTLD\"],\"okinawa\":[\"Geographic\"],\"on.ca\":[\"ccTLD\"],\"one\":[\"Novelty\"],\"ong\":[\"Business\",\"Community\"],\"onl\":[\"Popular\",\"gTLD\"],\"online\":[\"Featured\",\"Technology\"],\"ooo\":[\"Novelty\"],\"org\":[\"Popular\",\"gTLD\"],\"org.ag\":[\"ccTLD\"],\"org.au\":[\"ccTLD\",\"Geography\"],\"org.cn\":[\"ccTLD\",\"Geography\"],\"org.es\":[\"ccTLD\",\"Geography\"],\"org.in\":[\"ccTLD\"],\"org.nz\":[\"ccTLD\",\"Geography\"],\"org.pe\":[\"ccTLD\",\"Geography\"],\"org.pl\":[\"ccTLD\"],\"org.sc\":[\"ccTLD\"],\"org.tw\":[\"ccTLD\",\"Geography\"],\"org.uk\":[\"ccTLD\",\"Geography\"],\"organic\":[\"Identity and Lifestyle\",\"Interest\"],\"origins\":[\"Identity and Lifestyle\"],\"osaka\":[\"Community\",\"Geographic\"],\"ovh\":[\"Community\"],\"paris\":[\"Community\",\"Geographic\"],\"pars\":[\"Community\",\"Identity and Lifestyle\"],\"partners\":[\"Business\",\"Featured\",\"Popular\",\"gTLD\"],\"parts\":[\"Featured\",\"Popular\",\"gTLD\",\"Shopping\"],\"party\":[\"Leisure and Recreation\"],\"patagonia\":[\"Geographic\"],\"pay\":[\"Money and Finance\"],\"pe\":[\"ccTLD\",\"Geography\"],\"pe.ca\":[\"ccTLD\"],\"persiangulf\":[\"Geographic\",\"Identity and Lifestyle\"],\"pet\":[\"Interest\"],\"pets\":[\"Featured\",\"Interest\"],\"pharmacy\":[\"Community\",\"Services\"],\"phd\":[\"Education\",\"Identity and Lifestyle\"],\"phone\":[\"Featured\",\"Technology\"],\"photo\":[\"Popular\",\"gTLD\",\"Interest\",\"Services\"],\"photography\":[\"Arts and Entertainment\",\"Popular\",\"gTLD\",\"Interest\",\"Services\"],\"photos\":[\"Arts and Entertainment\",\"Popular\",\"gTLD\"],\"physio\":[\"Services\"],\"pics\":[\"Popular\",\"gTLD\",\"Interest\",\"Services\"],\"pictures\":[\"Arts and Entertainment\",\"Featured\",\"Popular\",\"gTLD\"],\"pid\":[\"Novelty\"],\"pink\":[\"Popular\",\"gTLD\",\"Novelty\"],\"pizza\":[\"Featured\",\"Food and Drink\"],\"pl\":[\"ccTLD\",\"Geography\"],\"place\":[\"Featured\",\"Geographic\",\"gTLD\"],\"play\":[\"Leisure and Recreation\"],\"plc.uk\":[\"ccTLD\"],\"plumbing\":[\"Popular\",\"gTLD\",\"Shopping\"],\"plus\":[\"Featured\",\"Novelty\"],\"poker\":[\"Featured\",\"Leisure and Recreation\"],\"porn\":[\"Adult\"],\"press\":[\"Business\"],\"pro\":[\"gTLD\",\"Specialty\",\"Sponsored\"],\"productions\":[\"Featured\",\"Popular\",\"gTLD\",\"Services\"],\"prof\":[\"Education\"],\"promo\":[\"Shopping\"],\"properties\":[\"Featured\",\"Popular\",\"gTLD\",\"Real Estate\"],\"property\":[\"Featured\",\"Real Estate\"],\"pub\":[\"Featured\",\"Food and Drink\",\"Popular\",\"gTLD\",\"Leisure and Recreation\"],\"pw\":[\"Business\",\"ccTLD\",\"Specialty\"],\"qc.ca\":[\"ccTLD\"],\"qc.com\":[\"ccTLD\",\"Geography\"],\"qh.cn\":[\"ccTLD\"],\"qpon\":[\"Shopping\"],\"quebec\":[\"Community\",\"Geographic\"],\"racing\":[\"Featured\",\"Interest\",\"Sports\"],\"radio\":[\"Arts and Entertainment\",\"Community\",\"Featured\"],\"realestate\":[\"Featured\",\"Real Estate\"],\"realtor\":[\"Real Estate\",\"Services\"],\"realty\":[\"Featured\",\"Real Estate\"],\"recht.pro\":[\"gTLD\",\"Specialty\",\"Sponsored\"],\"recipes\":[\"Food and Drink\",\"Popular\",\"gTLD\"],\"red\":[\"Featured\",\"Popular\",\"gTLD\",\"Novelty\"],\"rehab\":[\"Featured\",\"Services\"],\"reise\":[\"Leisure and Recreation\"],\"reisen\":[\"Featured\",\"gTLD\",\"Leisure and Recreation\"],\"reit\":[\"Community\",\"Money and Finance\"],\"ren\":[\"Identity and Lifestyle\"],\"rent\":[\"Featured\",\"Real Estate\"],\"rentals\":[\"Popular\",\"gTLD\",\"Services\"],\"repair\":[\"Popular\",\"gTLD\",\"Services\"],\"report\":[\"Featured\",\"Popular\",\"gTLD\",\"Interest\"],\"republican\":[\"Featured\",\"gTLD\",\"Identity and Lifestyle\"],\"rest\":[\"Food and Drink\",\"Popular\",\"gTLD\",\"Services\"],\"restaurant\":[\"Featured\",\"Food and Drink\"],\"review\":[\"Interest\"],\"reviews\":[\"Featured\",\"Popular\",\"gTLD\",\"Interest\"],\"rich\":[\"Popular\",\"gTLD\",\"Money and Finance\"],\"rio\":[\"Geographic\"],\"rip\":[\"Featured\",\"Novelty\"],\"rocks\":[\"Featured\",\"gTLD\",\"Novelty\"],\"rodeo\":[\"Popular\",\"gTLD\",\"Interest\",\"Sports\"],\"roma\":[\"Geographic\"],\"rsvp\":[\"Novelty\"],\"ru.com\":[\"ccTLD\",\"Other\"],\"rugby\":[\"Featured\",\"Interest\",\"Sports\"],\"ruhr\":[\"Geographic\"],\"run\":[\"Featured\",\"Interest\",\"Sports\"],\"ryukyu\":[\"Geographic\"],\"sa.com\":[\"ccTLD\",\"Other\"],\"saarland\":[\"Geographic\"],\"safe\":[\"Services\"],\"safety\":[\"Services\"],\"sale\":[\"Featured\",\"Shopping\"],\"salon\":[\"Featured\",\"Services\"],\"sarl\":[\"Business\",\"Featured\"],\"sas\":[\"Services\"],\"save\":[\"Shopping\"],\"sc\":[\"ccTLD\"],\"sc.cn\":[\"ccTLD\"],\"scholarships\":[\"Education\"],\"school\":[\"Education\",\"Featured\"],\"schule\":[\"Education\",\"Featured\",\"gTLD\"],\"science\":[\"Education\",\"Interest\"],\"scot\":[\"Community\",\"Geographic\",\"Identity and Lifestyle\"],\"sd.cn\":[\"ccTLD\"],\"se.com\":[\"ccTLD\",\"Other\"],\"se.net\":[\"ccTLD\",\"Other\"],\"search\":[\"Featured\",\"Technology\"],\"secure\":[\"Services\"],\"security\":[\"Featured\",\"Services\"],\"seek\":[\"Technology\"],\"services\":[\"Featured\",\"Popular\",\"gTLD\",\"Services\"],\"sex\":[\"Adult\"],\"sexy\":[\"Popular\",\"gTLD\",\"Interest\",\"Services\"],\"sg\":[\"ccTLD\",\"Geography\"],\"sh\":[\"ccTLD\",\"Geography\"],\"sh.cn\":[\"ccTLD\"],\"shia\":[\"Community\",\"Identity and Lifestyle\"],\"shiksha\":[\"Education\",\"Popular\",\"gTLD\"],\"shoes\":[\"Popular\",\"gTLD\",\"Shopping\"],\"shop\":[\"Community\",\"Featured\",\"Shopping\"],\"shopping\":[\"Featured\",\"Shopping\"],\"shopyourway\":[\"Shopping\"],\"show\":[\"Arts and Entertainment\",\"Featured\"],\"singles\":[\"Popular\",\"gTLD\",\"Shopping\"],\"site\":[\"Featured\",\"Technology\"],\"sk.ca\":[\"ccTLD\"],\"ski\":[\"Community\",\"Featured\",\"Interest\",\"Sports\"],\"skin\":[\"Identity and Lifestyle\"],\"sn.cn\":[\"ccTLD\"],\"soccer\":[\"Featured\",\"Interest\",\"Sports\"],\"social\":[\"Featured\",\"Popular\",\"gTLD\",\"Leisure and Recreation\"],\"software\":[\"Featured\",\"Technology\"],\"solar\":[\"Popular\",\"gTLD\",\"Interest\"],\"solutions\":[\"Business\",\"Popular\",\"gTLD\"],\"soy\":[\"Identity and Lifestyle\",\"Interest\"],\"spa\":[\"Community\",\"Featured\",\"Leisure and Recreation\"],\"space\":[\"Interest\"],\"sport\":[\"Community\",\"Interest\",\"Sports\"],\"sports\":[\"Featured\",\"Interest\",\"Sports\"],\"spot\":[\"Novelty\"],\"spreadbetting\":[\"Leisure and Recreation\"],\"srl\":[\"Business\"],\"stada\":[\"Community\"],\"stockholm\":[\"Geographic\"],\"storage\":[\"Featured\",\"Services\"],\"store\":[\"Featured\",\"Shopping\"],\"stroke\":[\"Identity and Lifestyle\"],\"studio\":[\"Business\",\"Featured\",\"Real Estate\"],\"study\":[\"Education\"],\"style\":[\"Featured\",\"Identity and Lifestyle\"],\"sucks\":[\"Featured\",\"Novelty\"],\"supplies\":[\"Featured\",\"Popular\",\"gTLD\",\"Shopping\"],\"supply\":[\"Featured\",\"Popular\",\"gTLD\",\"Shopping\"],\"support\":[\"Popular\",\"gTLD\",\"Services\"],\"surf\":[\"Leisure and Recreation\"],\"surgery\":[\"Featured\",\"gTLD\",\"Services\"],\"swiss\":[\"Community\",\"Geographic\"],\"sx.cn\":[\"ccTLD\"],\"sydney\":[\"Geographic\"],\"systems\":[\"Popular\",\"gTLD\",\"Technology\"],\"taipei\":[\"Geographic\"],\"tatar\":[\"Community\",\"Geographic\"],\"tattoo\":[\"Popular\",\"gTLD\",\"Interest\",\"Services\"],\"tax\":[\"Featured\",\"gTLD\",\"Money and Finance\"],\"taxi\":[\"Community\",\"Featured\",\"Services\"],\"tc\":[\"ccTLD\",\"Geography\"],\"team\":[\"Featured\",\"Interest\",\"Sports\"],\"tech\":[\"Featured\",\"Technology\"],\"technology\":[\"Popular\",\"gTLD\",\"Technology\"],\"tel\":[\"gTLD\",\"Specialty\",\"Sponsored\"],\"tennis\":[\"Community\",\"Featured\",\"Interest\",\"Sports\"],\"thai\":[\"Community\",\"Geographic\"],\"theater\":[\"Arts and Entertainment\",\"Featured\",\"Interest\"],\"theatre\":[\"Arts and Entertainment\",\"Interest\"],\"tickets\":[\"Featured\",\"Shopping\"],\"tienda\":[\"Featured\",\"Popular\",\"gTLD\",\"Shopping\"],\"tips\":[\"Popular\",\"gTLD\",\"Services\"],\"tires\":[\"Featured\",\"Shopping\"],\"tirol\":[\"Community\",\"Geographic\"],\"tj.cn\":[\"ccTLD\"],\"tk\":[\"ccTLD\"],\"tm\":[\"ccTLD\",\"Geography\",\"Specialty\"],\"today\":[\"Popular\",\"gTLD\",\"Novelty\"],\"tokyo\":[\"Geographic\",\"gTLD\"],\"tools\":[\"Featured\",\"Popular\",\"gTLD\",\"Shopping\"],\"top\":[\"Identity and Lifestyle\"],\"tour\":[\"Leisure and Recreation\"],\"tours\":[\"Featured\",\"Leisure and Recreation\",\"Services\"],\"town\":[\"Featured\",\"Geographic\",\"gTLD\"],\"toys\":[\"Featured\",\"gTLD\",\"Shopping\"],\"trade\":[\"Business\",\"Popular\",\"gTLD\"],\"trading\":[\"Business\",\"Featured\"],\"training\":[\"Popular\",\"gTLD\",\"Services\"],\"translations\":[\"Services\"],\"trust\":[\"Services\"],\"tube\":[\"Featured\",\"Technology\"],\"tv\":[\"ccTLD\",\"Geography\",\"Specialty\"],\"tw\":[\"ccTLD\",\"Geography\"],\"tw.cn\":[\"ccTLD\"],\"uk\":[\"ccTLD\",\"Popular\",\"Geographic\"],\"uk.com\":[\"ccTLD\",\"Other\"],\"uk.net\":[\"ccTLD\",\"Other\"],\"university\":[\"Education\",\"Featured\",\"gTLD\"],\"uno\":[\"Popular\",\"gTLD\",\"Identity and Lifestyle\"],\"us\":[\"ccTLD\",\"Popular\",\"Geography\"],\"us.com\":[\"ccTLD\",\"Other\"],\"us.org\":[\"ccTLD\",\"Other\"],\"uy.com\":[\"ccTLD\",\"Geography\"],\"vacations\":[\"Popular\",\"gTLD\",\"Leisure and Recreation\"],\"vana\":[\"Leisure and Recreation\"],\"vc\":[\"ccTLD\"],\"vegas\":[\"Geographic\",\"gTLD\",\"Leisure and Recreation\"],\"ventures\":[\"Popular\",\"gTLD\",\"Shopping\"],\"versicherung\":[\"Community\",\"Services\"],\"vet\":[\"Featured\",\"Identity and Lifestyle\",\"Services\"],\"vg\":[\"ccTLD\",\"Geography\"],\"viajes\":[\"Featured\",\"Popular\",\"gTLD\",\"Technology\"],\"video\":[\"Arts and Entertainment\",\"Featured\"],\"villas\":[\"Popular\",\"gTLD\",\"Real Estate\"],\"vin\":[\"Featured\",\"Services\"],\"vip\":[\"Featured\",\"Identity and Lifestyle\"],\"vision\":[\"Featured\",\"Popular\",\"gTLD\",\"Identity and Lifestyle\"],\"vlaanderen\":[\"Geographic\"],\"vodka\":[\"Food and Drink\",\"Popular\",\"gTLD\"],\"vote\":[\"Featured\",\"Identity and Lifestyle\"],\"voting\":[\"gTLD\",\"Identity and Lifestyle\"],\"voto\":[\"Identity and Lifestyle\"],\"voyage\":[\"Popular\",\"gTLD\",\"Leisure and Recreation\"],\"wales\":[\"Geographic\"],\"wang\":[\"Identity and Lifestyle\"],\"watch\":[\"Featured\",\"Popular\",\"gTLD\",\"Shopping\"],\"watches\":[\"Shopping\"],\"weather\":[\"Interest\",\"Services\"],\"web\":[\"Featured\",\"Technology\"],\"web.com\":[\"ccTLD\"],\"webcam\":[\"Popular\",\"gTLD\",\"Technology\"],\"webs\":[\"Community\"],\"website\":[\"Featured\",\"Technology\"],\"wed\":[\"Services\"],\"wedding\":[\"Featured\",\"Identity and Lifestyle\"],\"weibo\":[\"Technology\"],\"whoswho\":[\"Identity and Lifestyle\"],\"wien\":[\"Community\",\"Geographic\"],\"wiki\":[\"Popular\",\"gTLD\",\"Interest\"],\"win\":[\"Novelty\"],\"wine\":[\"Featured\",\"Food and Drink\"],\"winners\":[\"Novelty\"],\"work\":[\"Services\"],\"works\":[\"Featured\",\"Popular\",\"gTLD\",\"Services\"],\"world\":[\"Featured\",\"Geographic\"],\"wow\":[\"Featured\",\"Novelty\"],\"ws\":[\"ccTLD\",\"Specialty\"],\"wtf\":[\"Featured\",\"gTLD\",\"Novelty\"],\"xin\":[\"Business\"],\"xj.cn\":[\"ccTLD\"],\"xn--11b4c3d\":[\"IDN\"],\"xn--1ck2e1b\":[\"IDN\"],\"xn--1qqw23a\":[\"Geographic\",\"IDN\"],\"xn--30rr7y\":[\"IDN\"],\"xn--3bst00m\":[\"IDN\"],\"xn--3ds443g\":[\"Popular\",\"gTLD\",\"IDN\",\"Technology\"],\"xn--3pxu8k\":[\"IDN\"],\"xn--42c2d9a\":[\"IDN\"],\"xn--45q11c\":[\"IDN\",\"Interest\"],\"xn--4gbrim\":[\"IDN\",\"Technology\"],\"xn--4gq48lf9j\":[\"IDN\"],\"xn--55qw42g\":[\"IDN\"],\"xn--55qx5d\":[\"Business\",\"IDN\"],\"xn--5tzm5g\":[\"IDN\",\"Technology\"],\"xn--6frz82g\":[\"Popular\",\"gTLD\",\"IDN\",\"Technology\"],\"xn--6qq986b3xl\":[\"IDN\",\"Novelty\"],\"xn--6rtwn\":[\"Geographic\",\"IDN\"],\"xn--80adxhks\":[\"Geographic\",\"IDN\"],\"xn--80aqecdr1a\":[\"Community\",\"Identity and Lifestyle\",\"IDN\"],\"xn--80asehdb\":[\"Popular\",\"gTLD\",\"IDN\",\"Technology\"],\"xn--80aswg\":[\"Popular\",\"gTLD\",\"IDN\",\"Technology\"],\"xn--8y0a063a\":[\"IDN\"],\"xn--9et52u\":[\"Identity and Lifestyle\",\"IDN\",\"Shopping\"],\"xn--9krt00a\":[\"IDN\",\"Technology\"],\"xn--b4w605ferd\":[\"IDN\"],\"xn--c1avg\":[\"Popular\",\"gTLD\",\"IDN\"],\"xn--c1yn36f\":[\"IDN\"],\"xn--c2br7g\":[\"IDN\"],\"xn--cck2b3b\":[\"IDN\"],\"xn--cckwcxetd\":[\"IDN\"],\"xn--cg4bki\":[\"IDN\"],\"xn--czr694b\":[\"IDN\"],\"xn--czrs0t\":[\"Featured\",\"IDN\",\"Shopping\"],\"xn--czru2d\":[\"IDN\",\"Shopping\"],\"xn--d1acj3b\":[\"Identity and Lifestyle\",\"IDN\"],\"xn--dkwm73cwpn\":[\"IDN\"],\"xn--eckvdtc9d\":[\"IDN\"],\"xn--efvy88h\":[\"IDN\",\"Services\"],\"xn--estv75g\":[\"IDN\",\"Money and Finance\"],\"xn--fct429k\":[\"IDN\"],\"xn--fes124c\":[\"Geographic\",\"IDN\"],\"xn--fhbei\":[\"IDN\"],\"xn--fiq228c5hs\":[\"Popular\",\"gTLD\",\"IDN\",\"Technology\"],\"xn--fiq64b\":[\"IDN\"],\"xn--fjq720a\":[\"Arts and Entertainment\",\"Featured\",\"IDN\"],\"xn--flw351e\":[\"IDN\"],\"xn--g2xx48c\":[\"IDN\",\"Shopping\"],\"xn--gckr3f0f\":[\"IDN\"],\"xn--gk3at1e\":[\"IDN\"],\"xn--hdb9cza1b\":[\"IDN\"],\"xn--hxt814e\":[\"IDN\",\"Shopping\"],\"xn--i1b6b1a6a2e\":[\"IDN\"],\"xn--imr513n\":[\"Food and Drink\",\"IDN\"],\"xn--io0a7i\":[\"IDN\",\"Technology\"],\"xn--j1aef\":[\"IDN\"],\"xn--jlq480n2rg\":[\"IDN\"],\"xn--jlq61u9w7b\":[\"IDN\"],\"xn--jvr189m\":[\"IDN\"],\"xn--kpu716f\":[\"IDN\"],\"xn--kput3i\":[\"IDN\",\"Technology\"],\"xn--mgba3a3ejt\":[\"IDN\"],\"xn--mgbaakc7dvf\":[\"Business\",\"IDN\",\"Technology\"],\"xn--mgbab2bd\":[\"IDN\",\"Services\",\"Shopping\"],\"xn--mgbb9fbpob\":[\"IDN\"],\"xn--mgbca7dzdo\":[\"Geographic\",\"IDN\"],\"xn--mgbi4ecexp\":[\"Community\",\"Identity and Lifestyle\",\"IDN\"],\"xn--mgbt3dhd\":[\"IDN\"],\"xn--mgbv6cfpo\":[\"IDN\"],\"xn--mk1bu44c\":[\"IDN\"],\"xn--mxtq1m\":[\"IDN\"],\"xn--ngbc5azd\":[\"Popular\",\"gTLD\",\"IDN\",\"Novelty\"],\"xn--ngbe9e0a\":[\"IDN\"],\"xn--ngbrx\":[\"IDN\"],\"xn--nqv7f\":[\"Popular\",\"gTLD\",\"IDN\"],\"xn--nyqy26a\":[\"Identity and Lifestyle\",\"IDN\"],\"xn--otu796d\":[\"IDN\",\"Services\"],\"xn--p1acf\":[\"Community\",\"Identity and Lifestyle\",\"IDN\"],\"xn--pbt977c\":[\"IDN\",\"Shopping\"],\"xn--pgb3ceoj\":[\"IDN\"],\"xn--pssy2u\":[\"IDN\"],\"xn--q9jyb4c\":[\"Popular\",\"gTLD\",\"IDN\",\"Novelty\"],\"xn--qcka1pmc\":[\"IDN\"],\"xn--rhqv96g\":[\"IDN\"],\"xn--rovu88b\":[\"IDN\"],\"xn--ses554g\":[\"IDN\",\"Technology\"],\"xn--t60b56a\":[\"IDN\"],\"xn--tckwe\":[\"IDN\"],\"xn--tiq49xqyj\":[\"Community\",\"Identity and Lifestyle\",\"IDN\"],\"xn--unup4y\":[\"Featured\",\"IDN\",\"Leisure and Recreation\"],\"xn--vhquv\":[\"Business\",\"Featured\",\"IDN\"],\"xn--vuq861b\":[\"IDN\"],\"xn--w4rs40l\":[\"IDN\"],\"xn--xhq521b\":[\"Community\",\"Geographic\",\"IDN\"],\"xn--zfr164b\":[\"Community\",\"IDN\"],\"xxx\":[\"gTLD\",\"Specialty\",\"Sponsored\"],\"xyz\":[\"Popular\",\"gTLD\",\"Novelty\"],\"xz.cn\":[\"ccTLD\"],\"yachts\":[\"Leisure and Recreation\"],\"yk.ca\":[\"ccTLD\"],\"yoga\":[\"Featured\",\"Interest\",\"Sports\"],\"yokohama\":[\"Geographic\"],\"you\":[\"Novelty\"],\"za.com\":[\"ccTLD\",\"Other\"],\"zip\":[\"Technology\"],\"zone\":[\"Popular\",\"gTLD\",\"Novelty\"],\"zuerich\":[\"Geographic\"],\"zulu\":[\"Identity and Lifestyle\"]}";
        $topLevelDomainsAndCategories = json_decode($rawTopLevelDomainsAndCategories, true);
        $primaryCategories = array("Popular", "gTLD", "ccTLD", "Specialty", "Sponsored", "IDN", "WatchList", "Other");
        $categoryModels = array();
        foreach ($topLevelDomainsAndCategories as $topLevelDomain => $categories) {
            $tld = new \WHMCS\Domain\TopLevel();
            $tld->tld = $topLevelDomain;
            $tld->save();
            foreach ($categories as $category) {
                if (!isset($categoryModels[$category])) {
                    $categoryModel = new \WHMCS\Domain\TopLevel\Category();
                    $categoryModel->category = $category;
                    $categoryModel->isPrimary = in_array($category, $primaryCategories);
                    $categoryModel->displayOrder = in_array($category, $primaryCategories) ? array_search($category, $primaryCategories) : null;
                    $categoryModel->save();
                    $categoryModels[$category] = $categoryModel;
                }
                $categoryModel = $categoryModels[$category];
                $tld->categories()->attach($categoryModel);
            }
        }
        return $this;
    }
    protected function migrateDiscontinuedOrderFormTemplates()
    {
        $discontinuedTemplates = array("ajaxcart" => "cart", "web20cart" => "boxes");
        foreach ($discontinuedTemplates as $discontinuedTemplate => $templateToMigrateTo) {
            \WHMCS\Product\Group::where("orderfrmtpl", "=", $discontinuedTemplate)->update(array("orderfrmtpl" => $templateToMigrateTo));
            if (\WHMCS\Config\Setting::getValue("OrderFormTemplate") == $discontinuedTemplate) {
                \WHMCS\Config\Setting::setValue("OrderFormTemplate", $templateToMigrateTo);
            }
        }
        return $this;
    }
    protected function migrateDiscontinuedAdminOriginalTemplate()
    {
        $admin = \WHMCS\User\Admin::where("template", "=", "original");
        $admin->getModel()->timestamps = false;
        $admin->update(array("template" => "blend"));
        return $this;
    }
    protected function convertContactUnixTimestampColumns()
    {
        $columns = array("pwresetexpiry");
        foreach ($columns as $column) {
            \WHMCS\User\Client\Contact::convertUnixTimestampIntegerToTimestampColumn($column);
        }
        return $this;
    }
}

?>