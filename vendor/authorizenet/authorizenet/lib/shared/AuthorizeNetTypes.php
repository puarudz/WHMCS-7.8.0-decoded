<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * Classes for the various AuthorizeNet data types.
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetCIM
 */
/**
 * A class that contains all fields for a CIM Customer Profile.
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetCIM
 */
class AuthorizeNetCustomer
{
    public $merchantCustomerId;
    public $description;
    public $email;
    public $paymentProfiles = array();
    public $shipToList = array();
    public $customerProfileId;
}
/**
 * A class that contains all fields for a CIM Address.
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetCIM
 */
class AuthorizeNetAddress
{
    public $firstName;
    public $lastName;
    public $company;
    public $address;
    public $city;
    public $state;
    public $zip;
    public $country;
    public $phoneNumber;
    public $faxNumber;
    public $customerAddressId;
}
/**
 * A class that contains all fields for a CIM Payment Profile.
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetCIM
 */
class AuthorizeNetPaymentProfile
{
    public $customerType;
    public $billTo;
    public $payment;
    public $customerPaymentProfileId;
    public function __construct()
    {
        $this->billTo = new AuthorizeNetAddress();
        $this->payment = new AuthorizeNetPayment();
    }
}
/**
 * A class that contains all fields for a CIM Payment Type.
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetCIM
 */
class AuthorizeNetPayment
{
    public $creditCard;
    public $bankAccount;
    public function __construct()
    {
        $this->creditCard = new AuthorizeNetCreditCard();
        $this->bankAccount = new AuthorizeNetBankAccount();
    }
}
/**
 * A class that contains all fields for a CIM Transaction.
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetCIM
 */
class AuthorizeNetTransaction
{
    public $amount;
    public $tax;
    public $shipping;
    public $duty;
    public $lineItems = array();
    public $customerProfileId;
    public $customerPaymentProfileId;
    public $customerShippingAddressId;
    public $creditCardNumberMasked;
    public $bankRoutingNumberMasked;
    public $bankAccountNumberMasked;
    public $order;
    public $taxExempt;
    public $recurringBilling;
    public $cardCode;
    public $splitTenderId;
    public $approvalCode;
    public $transId;
    public function __construct()
    {
        $this->tax = (object) array();
        $this->tax->amount = "";
        $this->tax->name = "";
        $this->tax->description = "";
        $this->shipping = (object) array();
        $this->shipping->amount = "";
        $this->shipping->name = "";
        $this->shipping->description = "";
        $this->duty = (object) array();
        $this->duty->amount = "";
        $this->duty->name = "";
        $this->duty->description = "";
        // line items
        $this->order = (object) array();
        $this->order->invoiceNumber = "";
        $this->order->description = "";
        $this->order->purchaseOrderNumber = "";
    }
}
/**
 * A class that contains all fields for a CIM Transaction Line Item.
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetCIM
 */
class AuthorizeNetLineItem
{
    public $itemId;
    public $name;
    public $description;
    public $quantity;
    public $unitPrice;
    public $taxable;
}
/**
 * A class that contains all fields for a CIM Credit Card.
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetCIM
 */
class AuthorizeNetCreditCard
{
    public $cardNumber;
    public $expirationDate;
    public $cardCode;
}
/**
 * A class that contains all fields for a CIM Bank Account.
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetCIM
 */
class AuthorizeNetBankAccount
{
    public $accountType;
    public $routingNumber;
    public $accountNumber;
    public $nameOnAccount;
    public $echeckType;
    public $bankName;
}
/**
 * A class that contains all fields for an AuthorizeNet ARB Subscription.
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetARB
 */
class AuthorizeNet_Subscription
{
    public $name;
    public $intervalLength;
    public $intervalUnit;
    public $startDate;
    public $totalOccurrences;
    public $trialOccurrences;
    public $amount;
    public $trialAmount;
    public $creditCardCardNumber;
    public $creditCardExpirationDate;
    public $creditCardCardCode;
    public $bankAccountAccountType;
    public $bankAccountRoutingNumber;
    public $bankAccountAccountNumber;
    public $bankAccountNameOnAccount;
    public $bankAccountEcheckType;
    public $bankAccountBankName;
    public $orderInvoiceNumber;
    public $orderDescription;
    public $customerId;
    public $customerEmail;
    public $customerPhoneNumber;
    public $customerFaxNumber;
    public $billToFirstName;
    public $billToLastName;
    public $billToCompany;
    public $billToAddress;
    public $billToCity;
    public $billToState;
    public $billToZip;
    public $billToCountry;
    public $shipToFirstName;
    public $shipToLastName;
    public $shipToCompany;
    public $shipToAddress;
    public $shipToCity;
    public $shipToState;
    public $shipToZip;
    public $shipToCountry;
    public function getXml()
    {
        $xml = "<subscription>\n    <name>{$this->name}</name>\n    <paymentSchedule>\n        <interval>\n            <length>{$this->intervalLength}</length>\n            <unit>{$this->intervalUnit}</unit>\n        </interval>\n        <startDate>{$this->startDate}</startDate>\n        <totalOccurrences>{$this->totalOccurrences}</totalOccurrences>\n        <trialOccurrences>{$this->trialOccurrences}</trialOccurrences>\n    </paymentSchedule>\n    <amount>{$this->amount}</amount>\n    <trialAmount>{$this->trialAmount}</trialAmount>\n    <payment>\n        <creditCard>\n            <cardNumber>{$this->creditCardCardNumber}</cardNumber>\n            <expirationDate>{$this->creditCardExpirationDate}</expirationDate>\n            <cardCode>{$this->creditCardCardCode}</cardCode>\n        </creditCard>\n        <bankAccount>\n            <accountType>{$this->bankAccountAccountType}</accountType>\n            <routingNumber>{$this->bankAccountRoutingNumber}</routingNumber>\n            <accountNumber>{$this->bankAccountAccountNumber}</accountNumber>\n            <nameOnAccount>{$this->bankAccountNameOnAccount}</nameOnAccount>\n            <echeckType>{$this->bankAccountEcheckType}</echeckType>\n            <bankName>{$this->bankAccountBankName}</bankName>\n        </bankAccount>\n    </payment>\n    <order>\n        <invoiceNumber>{$this->orderInvoiceNumber}</invoiceNumber>\n        <description>{$this->orderDescription}</description>\n    </order>\n    <customer>\n        <id>{$this->customerId}</id>\n        <email>{$this->customerEmail}</email>\n        <phoneNumber>{$this->customerPhoneNumber}</phoneNumber>\n        <faxNumber>{$this->customerFaxNumber}</faxNumber>\n    </customer>\n    <billTo>\n        <firstName>{$this->billToFirstName}</firstName>\n        <lastName>{$this->billToLastName}</lastName>\n        <company>{$this->billToCompany}</company>\n        <address>{$this->billToAddress}</address>\n        <city>{$this->billToCity}</city>\n        <state>{$this->billToState}</state>\n        <zip>{$this->billToZip}</zip>\n        <country>{$this->billToCountry}</country>\n    </billTo>\n    <shipTo>\n        <firstName>{$this->shipToFirstName}</firstName>\n        <lastName>{$this->shipToLastName}</lastName>\n        <company>{$this->shipToCompany}</company>\n        <address>{$this->shipToAddress}</address>\n        <city>{$this->shipToCity}</city>\n        <state>{$this->shipToState}</state>\n        <zip>{$this->shipToZip}</zip>\n        <country>{$this->shipToCountry}</country>\n    </shipTo>\n</subscription>";
        $xml_clean = "";
        // Remove any blank child elements
        foreach (preg_split("/(\r?\n)/", $xml) as $key => $line) {
            if (!preg_match('/><\\//', $line)) {
                $xml_clean .= $line . "\n";
            }
        }
        // Remove any blank parent elements
        $element_removed = 1;
        // Recursively repeat if a change is made
        while ($element_removed) {
            $element_removed = 0;
            if (preg_match('/<[a-z]+>[\\r?\\n]+\\s*<\\/[a-z]+>/i', $xml_clean)) {
                $xml_clean = preg_replace('/<[a-z]+>[\\r?\\n]+\\s*<\\/[a-z]+>/i', '', $xml_clean);
                $element_removed = 1;
            }
        }
        // Remove any blank lines
        // $xml_clean = preg_replace('/\r\n[\s]+\r\n/','',$xml_clean);
        return $xml_clean;
    }
}
/**
 * A class that contains all fields for an AuthorizeNet ARB SubscriptionList.
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetARB
 */
class AuthorizeNetGetSubscriptionList
{
    public $searchType;
    public $sorting;
    public $paging;
    public function getXml()
    {
        $emptyString = "";
        $sortingXml = is_null($this->sorting) ? $emptyString : $this->sorting->getXml();
        $pagingXml = is_null($this->paging) ? $emptyString : $this->paging->getXml();
        $xml = "\n        <searchType>{$this->searchType}</searchType>" . $sortingXml . $pagingXml;
        $xml_clean = "";
        // Remove any blank child elements
        foreach (preg_split("/(\r?\n)/", $xml) as $key => $line) {
            if (!preg_match('/><\\//', $line)) {
                $xml_clean .= $line . "\n";
            }
        }
        // Remove any blank parent elements
        $element_removed = 1;
        // Recursively repeat if a change is made
        while ($element_removed) {
            $element_removed = 0;
            if (preg_match('/<[a-z]+>[\\r?\\n]+\\s*<\\/[a-z]+>/i', $xml_clean)) {
                $xml_clean = preg_replace('/<[a-z]+>[\\r?\\n]+\\s*<\\/[a-z]+>/i', '', $xml_clean);
                $element_removed = 1;
            }
        }
        // Remove any blank lines
        // $xml_clean = preg_replace('/\r\n[\s]+\r\n/','',$xml_clean);
        return $xml_clean;
    }
}
class AuthorizeNetSubscriptionListPaging
{
    public $limit;
    public $offset;
    public function getXml()
    {
        $xml = "<paging>\n            <limit>{$this->limit}</limit>\n            <offset>{$this->offset}</offset>\n        </paging>";
        return $xml;
    }
}
class AuthorizeNetSubscriptionListSorting
{
    public $orderBy;
    public $orderDescending;
    public function getXml()
    {
        $xml = "\n        <sorting>\n            <orderBy>{$this->orderBy}</orderBy>\n            <orderDescending>{$this->orderDescending}</orderDescending>\n        </sorting>";
        return $xml;
    }
}

?>