<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Client\Menu;

class SecondaryNavbarFactory extends PrimaryNavbarFactory
{
    protected $rootItemName = "Secondary Navbar";
    public function navbar($firstName = "", array $conditionalLinks = array())
    {
        $menuStructure = \WHMCS\Session::get("uid") ? $this->getLoggedInNavBarStructure($firstName, $conditionalLinks) : $this->getLoggedOutNavBarStructure($conditionalLinks);
        return $this->loader->load($this->buildMenuStructure($menuStructure));
    }
    protected function getLoggedOutNavBarStructure(array $conditionalLinks = array())
    {
        $navbarStructure = array(array("name" => "Account", "label" => \Lang::trans("account"), "uri" => "#", "order" => 10, "children" => array(array("name" => "Login", "label" => \Lang::trans("login"), "uri" => "clientarea.php", "order" => 10), array("name" => "Divider", "label" => "-----", "order" => 30, "attributes" => array("class" => "nav-divider")), array("name" => "Forgot Password?", "label" => \Lang::trans("forgotpw"), "uri" => routePath("password-reset-begin"), "order" => 40))));
        if (!empty($conditionalLinks["allowClientRegistration"])) {
            $navbarStructure[0]["children"][] = array("name" => "Register", "label" => \Lang::trans("register"), "uri" => "register.php", "order" => 20);
        }
        return $navbarStructure;
    }
    protected function getLoggedInNavBarStructure($firstName = "", array $conditionalLinks = array())
    {
        return array(array("name" => "Account", "label" => sprintf(\Lang::trans("helloname"), \WHMCS\Input\Sanitize::makeSafeForOutput($firstName)), "uri" => "#", "order" => 10, "children" => $this->buildAccountChildren($conditionalLinks), "attributes" => array("class" => "account")));
    }
    protected function buildAccountChildren(array $conditionalLinks = array())
    {
        $accountChildren = array(array("name" => "Edit Account Details", "label" => \Lang::trans("editaccountdetails"), "uri" => "clientarea.php?action=details", "order" => 10));
        if (!empty($conditionalLinks["updatecc"])) {
            $accountChildren[] = array("name" => "Payment Methods", "label" => \Lang::trans("paymentMethods.title"), "uri" => routePath("account-paymentmethods"), "order" => 20);
        }
        $accountChildren[] = array("name" => "Contacts/Sub-Accounts", "label" => \Lang::trans("clientareanavcontacts"), "uri" => "clientarea.php?action=contacts", "order" => 30);
        $accountChildren[] = array("name" => "Change Password", "label" => \Lang::trans("clientareanavchangepw"), "uri" => "clientarea.php?action=changepw", "order" => 40);
        if (!empty($conditionalLinks["security"])) {
            $accountChildren[] = array("name" => "Security Settings", "label" => \Lang::trans("clientareanavsecurity"), "uri" => "clientarea.php?action=security", "order" => 50);
        }
        $accountChildren[] = array("name" => "Email History", "label" => \Lang::trans("navemailssent"), "uri" => "clientarea.php?action=emails", "order" => 70);
        $accountChildren[] = array("name" => "Divider", "label" => "-----", "order" => 80, "attributes" => array("class" => "nav-divider"));
        $accountChildren[] = array("name" => "Logout", "label" => \Lang::trans("clientareanavlogout"), "uri" => "logout.php", "order" => 90);
        return $accountChildren;
    }
}

?>