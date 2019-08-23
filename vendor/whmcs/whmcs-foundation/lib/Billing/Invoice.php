<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Billing;

class Invoice extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblinvoices";
    protected $dates = array("date", "dateCreated", "duedate", "dateDue", "datepaid", "datePaid", "lastCaptureAttempt");
    protected $columnMap = array("clientId" => "userid", "invoiceNumber" => "invoicenum", "dateCreated" => "date", "dateDue" => "duedate", "datePaid" => "datepaid", "tax1" => "tax", "taxRate1" => "taxrate", "paymentGateway" => "paymentmethod", "adminNotes" => "notes", "lineItems" => "items");
    public $timestamps = false;
    protected $appends = array("balance", "paymentGatewayName", "amountPaid");
    const STATUS_DRAFT = "Draft";
    const STATUS_UNPAID = "Unpaid";
    const STATUS_PAID = "Paid";
    public static function boot()
    {
        parent::boot();
        self::created(function (Invoice $invoice) {
            \WHMCS\Invoices::adjustIncrementForNextInvoice($invoice->id);
            try {
                $data = new Invoice\Data();
                $clientCountry = $invoice->client->country;
                if (!$clientCountry) {
                    $clientCountry = \WHMCS\Config\Setting::getValue("DefaultCountry");
                }
                $data->country = $clientCountry;
                $invoice->data()->save($data);
            } catch (\Exception $e) {
            }
        });
        self::deleting(function (Invoice $invoice) {
            if ($invoice->data) {
                $invoice->data->delete();
            }
            if ($invoice->snapshot) {
                $invoice->snapshot->delete();
            }
        });
        self::saving(function (Invoice $invoice) {
            if (\WHMCS\Config\Setting::getValue("TaxCustomInvoiceNumbering") && $invoice->invoiceNumber == "" && $invoice->status == self::STATUS_UNPAID && (!$invoice->exists || $invoice->getOriginal("status") != self::STATUS_UNPAID)) {
                $invoice->vat()->setCustomInvoiceNumberFormat();
            }
            if ($invoice->status == self::STATUS_PAID && $invoice->getOriginal("status") != self::STATUS_PAID) {
                $invoice->vat()->setInvoiceDateOnPayment();
            }
        });
        self::saved(function (Invoice $invoice) {
            if ($invoice->status == self::STATUS_UNPAID && \WHMCS\Config\Setting::getValue("StoreClientDataSnapshotOnInvoiceCreation") && Invoice\Snapshot::where("invoiceid", $invoice->id)->count() === 0) {
                $client = new \WHMCS\Client($invoice->client);
                $clientsDetails = $client->getDetails("billing");
                unset($clientsDetails["model"]);
                $customFields = array();
                $fields = \WHMCS\Database\Capsule::table("tblcustomfields")->leftJoin("tblcustomfieldsvalues", "tblcustomfields.id", "=", "tblcustomfieldsvalues.fieldid")->where("tblcustomfieldsvalues.relid", $invoice->clientId)->where("type", "client")->where("showinvoice", "on")->get(array("tblcustomfields.id as id", "tblcustomfields.fieldname as fieldName", "tblcustomfieldsvalues.value as value"));
                foreach ($fields as $field) {
                    if ($field->value) {
                        $customFields[] = array("id" => $field->id, "fieldname" => $field->fieldName, "value" => $field->value);
                    }
                }
                Invoice\Snapshot::firstOrCreate(array("invoiceid" => $invoice->id, "clientsdetails" => $clientsDetails, "customfields" => $customFields));
            }
        });
    }
    public function client()
    {
        return $this->belongsTo("WHMCS\\User\\Client", "userid");
    }
    public function transactions()
    {
        return $this->hasMany("WHMCS\\Billing\\Payment\\Transaction", "invoiceid");
    }
    public function items()
    {
        return $this->hasMany("WHMCS\\Billing\\Invoice\\Item", "invoiceid");
    }
    public function snapshot()
    {
        return $this->hasOne("WHMCS\\Billing\\Invoice\\Snapshot", "invoiceid");
    }
    public function order()
    {
        return $this->belongsTo("WHMCS\\Order\\Order", "id", "invoiceid");
    }
    public function scopeUnpaid(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereStatus(self::STATUS_UNPAID);
    }
    public function scopeOverdue(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereStatus(self::STATUS_UNPAID)->where("duedate", "<", \WHMCS\Carbon::now()->format("Y-m-d"));
    }
    public function scopePaid(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereStatus(self::STATUS_PAID);
    }
    public function scopeCancelled(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereStatus("Cancelled");
    }
    public function scopeRefunded(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereStatus("Refunded");
    }
    public function scopeCollections(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereStatus("Collections");
    }
    public function scopePaymentPending(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereStatus("Payment Pending");
    }
    public function scopeMassPay(\Illuminate\Database\Eloquent\Builder $query, $isMassPay = true)
    {
        return $query->where(function ($query) use($isMassPay) {
            $query->whereHas("items", function ($query) use($isMassPay) {
                $query->where("type", $isMassPay ? "=" : "!=", "Invoice");
            });
            if (!$isMassPay) {
                $query->orHas("items", "=", 0);
            }
        });
    }
    public function scopeWithLastCaptureAttempt(\Illuminate\Database\Eloquent\Builder $query, \WHMCS\Carbon $date)
    {
        return $query->where("last_capture_attempt", ">=", $date->toDateString())->where("last_capture_attempt", "<=", $date->toDateString() . " 23:59:59");
    }
    public function getBalanceAttribute()
    {
        $totalDue = $this->total;
        $transactions = $this->transactions();
        if (0 < $transactions->count()) {
            $totalDue = $totalDue - $transactions->sum("amountin") + $transactions->sum("amountout");
        }
        return $totalDue;
    }
    public function getPaymentGatewayNameAttribute()
    {
        $gateway = $this->paymentGateway;
        try {
            $gatewayName = \WHMCS\Module\Gateway::factory($gateway)->getDisplayName();
        } catch (\Exception $e) {
            $gatewayName = $gateway;
        }
        return $gatewayName;
    }
    public function getAmountPaidAttribute()
    {
        $amountPaid = 0;
        $transactions = $this->transactions();
        if (0 < $transactions->count()) {
            $amountPaid = $transactions->sum("amountin") - $transactions->sum("amountout");
        }
        return $amountPaid;
    }
    public function addPayment($amount, $transactionId = "", $fees = 0, $gateway = "", $noEmail = false, \WHMCS\Carbon $date = NULL)
    {
        if (!$amount) {
            throw new \WHMCS\Exception\Module\NotServicable("Amount is Required");
        }
        if ($amount < 0) {
            throw new \WHMCS\Exception\Module\NotServicable("Payment Amount Must be Greater than Zero");
        }
        if (!function_exists("addTransaction")) {
            require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "invoicefunctions.php";
        }
        $invoiceId = $this->id;
        if (!$gateway) {
            $gateway = $this->paymentGateway;
        }
        $userId = $this->clientId;
        $status = $this->status;
        if (in_array($status, array("Cancelled", "Draft"))) {
            throw new \WHMCS\Exception\Module\NotServicable("Payments can only be applied to invoices in Unpaid, Paid, Refunded or Collections statuses");
        }
        if (!$date) {
            $date = \WHMCS\Carbon::now();
        }
        addTransaction($userId, 0, "Invoice Payment", $amount, $fees, 0, $gateway, $transactionId, $invoiceId, fromMySQLDate($date->toDateTimeString()));
        $balance = format_as_currency($this->balance);
        logActivity("Added Invoice Payment - Invoice ID: " . $invoiceId, $userId);
        run_hook("AddInvoicePayment", array("invoiceid" => $invoiceId));
        if ($balance <= 0 && in_array($status, array("Unpaid", "Payment Pending"))) {
            processPaidInvoice($invoiceId, $noEmail, fromMySQLDate($date));
        } else {
            if (!$noEmail) {
                sendMessage("Invoice Payment Confirmation", $invoiceId);
            }
        }
        if ($balance <= 0) {
            $amountCredited = \WHMCS\Database\Capsule::table("tblcredit")->where("relid", $invoiceId)->sum("amount");
            $balance = $balance + $amountCredited;
            if ($balance < 0) {
                $balance = $balance * -1;
                \WHMCS\Database\Capsule::table("tblcredit")->insert(array("clientid" => $userId, "date" => $date->toDateTimeString(), "description" => "Invoice #" . $invoiceId . " Overpayment", "amount" => $balance, "relid" => $invoiceId));
                $this->client->credit += $balance;
                $this->client->save();
            }
        }
        return true;
    }
    public function getBillingValues()
    {
        if (!function_exists("getBillingCycleMonths")) {
            include_once ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "invoicefunctions.php";
        }
        $cycles = new Cycles();
        $paidAmount = $this->amountPaid;
        $taxEnabled = (bool) \WHMCS\Config\Setting::getValue("TaxEnabled");
        $taxType = \WHMCS\Config\Setting::getValue("TaxType");
        $compoundTax = \WHMCS\Config\Setting::getValue("TaxL2Compound");
        $taxCalculator = null;
        if ($taxEnabled) {
            $taxCalculator = new Tax();
            $taxCalculator->setIsInclusive($taxType == "Inclusive")->setIsCompound($compoundTax)->setLevel1Percentage($this->taxRate1)->setLevel2Percentage($this->taxRate2);
        }
        $items = array();
        foreach ($this->items as $invoiceItem) {
            $packageData = null;
            $lineAmount = $invoiceItem->amount;
            switch ($invoiceItem->type) {
                case "Addon":
                    $itemId = "A" . $invoiceItem->addon->id;
                    $billingCycle = $invoiceItem->addon->billingCycle;
                    $amount = $invoiceItem->addon->recurringFee;
                    $extraItem = $this->items->where("type", "PromoAddon")->where("relid", $invoiceItem->relatedEntityId)->first();
                    if ($extraItem) {
                        $amount += $extraItem->amount;
                        $lineAmount += $extraItem->amount;
                    }
                    break;
                case "Hosting":
                    $itemId = "H" . $invoiceItem->service->id;
                    $billingCycle = $invoiceItem->service->billingCycle;
                    $amount = $invoiceItem->service->recurringAmount;
                    $packageData = $invoiceItem->service->product;
                    try {
                        if ($cycles->getNumberOfMonths($billingCycle) === 0) {
                            $amount = $invoiceItem->service->firstPaymentAmount;
                        }
                    } catch (\Exception $e) {
                    }
                    $extraItem = $this->items->where("type", "PromoHosting")->where("relid", $invoiceItem->relatedEntityId)->first();
                    if ($extraItem) {
                        $amount += $extraItem->amount;
                        $lineAmount += $extraItem->amount;
                    }
                    break;
                case "Domain":
                case "DomainTransfer":
                case "DomainRegister":
                    $itemId = "D" . $invoiceItem->domain->id;
                    $registrationPeriod = $invoiceItem->domain->registrationPeriod;
                    $amount = $invoiceItem->domain->recurringAmount;
                    if (3 < $registrationPeriod) {
                        $billingCycle = "One Time";
                    } else {
                        if ($registrationPeriod == 1) {
                            $billingCycle = "Annually";
                        } else {
                            if ($registrationPeriod == 2) {
                                $billingCycle = "Biennially";
                            } else {
                                $billingCycle = "Triennially";
                            }
                        }
                    }
                    $extraItem = $this->items->where("type", "PromoDomain")->where("relid", $invoiceItem->relatedEntityId)->first();
                    if ($extraItem) {
                        $amount += $extraItem->amount;
                        $lineAmount += $extraItem->amount;
                    }
                    break;
                case "PromoAddon":
                case "PromoDomain":
                case "PromoHosting":
                    continue 2;
                default:
                    $amount = $invoiceItem->amount;
                    $itemId = "i" . $invoiceItem->invoiceId . "_" . $invoiceItem->id;
                    $billingCycle = "One Time";
            }
            if ($taxEnabled && $invoiceItem->taxed && $taxCalculator) {
                $taxCalculator->setTaxBase($amount);
                $amount = $taxCalculator->getTotalAfterTaxes();
                $taxCalculator->setTaxBase($lineAmount);
                $lineAmount = $taxCalculator->getTotalAfterTaxes();
            }
            try {
                $recurringCyclePeriod = $cycles->getNumberOfMonths($billingCycle);
            } catch (\Exception $e) {
                $recurringCyclePeriod = 0;
            }
            $recurringCycleUnits = "Months";
            if (12 <= $recurringCyclePeriod) {
                $recurringCyclePeriod = $recurringCyclePeriod / 12;
                $recurringCycleUnits = "Years";
            }
            $firstCyclePeriod = $recurringCyclePeriod;
            $firstCycleUnits = $recurringCycleUnits;
            if ($invoiceItem->type == "Hosting" && $packageData && $packageData->proRataBilling && $invoiceItem->service->registrationDate == $invoiceItem->service->nextDueDate) {
                $proRataValues = null;
                $registrationDate = $invoiceItem->service->registrationDate;
                if ($registrationDate instanceof \WHMCS\Carbon) {
                    $day = $registrationDate->format("d");
                    $month = $registrationDate->format("m");
                    $year = $registrationDate->format("Y");
                } else {
                    $day = substr($registrationDate, 8, 2);
                    $month = substr($registrationDate, 5, 2);
                    $year = substr($registrationDate, 0, 4);
                }
                $proRataValues = getProrataValues($billingCycle, 0, $packageData->proRataChargeDayOfCurrentMonth, $packageData->proRataChargeNextMonthAfterDay, $day, $month, $year, $this->clientId);
                $firstCyclePeriod = $proRataValues["days"];
                $firstCycleUnits = "Days";
            }
            $firstPaymentAmount = $amount;
            if ($paidAmount) {
                if ($amount < $paidAmount) {
                    $firstPaymentAmount = 0;
                    $paidAmount -= $amount;
                } else {
                    $firstPaymentAmount = $amount - $paidAmount;
                    $paidAmount = 0;
                }
            }
            $convertTo = \WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", $this->paymentGateway)->where("setting", "convertto")->value("value");
            if ($convertTo) {
                $firstPaymentAmount = convertCurrency($firstPaymentAmount, $this->client->currencyId, $convertTo);
                $amount = convertCurrency($amount, $this->client->currencyId, $convertTo);
                $lineAmount = convertCurrency($lineAmount, $this->client->currencyId, $convertTo);
            }
            $setupFee = 0;
            if ($invoiceItem->type == "Addon" && $invoiceItem->addon->registrationDate == $invoiceItem->addon->nextDueDate && 0 < $invoiceItem->addon->setupFee) {
                $setupFee = $invoiceItem->addon->setupFee;
            }
            if ($setupFee && $convertTo) {
                $setupFee = convertCurrency($setupFee, $this->client->currencyId, $convertTo);
            }
            if (substr($invoiceItem->type, 0, 6) == "Domain" && $invoiceItem->domain->registrationDate == $invoiceItem->domain->nextDueDate && $invoiceItem->domain->firstPaymentAmount != $invoiceItem->domain->recurringAmount) {
                $domainFirstPayment = $invoiceItem->domain->firstPaymentAmount;
                $domainRecurringAmount = $invoiceItem->domain->recurringAmount;
                if ($domainFirstPayment == 0) {
                    $setupFee = $domainRecurringAmount * -1;
                } else {
                    $setupFee = ($domainRecurringAmount - $domainFirstPayment) * -1;
                }
            }
            if ($setupFee && $convertTo) {
                $setupFee = convertCurrency($setupFee, $this->client->currencyId, $convertTo);
            }
            $firstPaymentAmount = format_as_currency($firstPaymentAmount);
            $amount = format_as_currency($amount);
            $setupFee = format_as_currency($setupFee);
            $item = array("itemId" => $itemId, "amount" => $amount, "setupFee" => $setupFee, "recurringCyclePeriod" => $recurringCyclePeriod, "recurringCycleUnits" => $recurringCycleUnits, "description" => $invoiceItem->description, "lineItemAmount" => $lineAmount);
            if ($firstPaymentAmount != $amount) {
                array_merge($item, array("firstPaymentAmount" => $firstPaymentAmount, "firstCyclePeriod" => $firstCyclePeriod, "firstCycleUnits" => $firstCycleUnits));
            }
            $items[] = $item;
        }
        $items["overdue"] = $this->dateDue < \WHMCS\Carbon::now()->format("Y-m-d");
        return $items;
    }
    public function shouldRenewRun($relatedId, $registrationDate, $type = "Hosting")
    {
        if (!in_array($type, array("Hosting", "Addon"))) {
            throw new \WHMCS\Exception\Module\NotServicable("Invalid Type for Comparison");
        }
        $table = "tblhosting";
        if ($type == "Addon") {
            $table = "tblhostingaddons";
        }
        $orderInvoice = \WHMCS\Database\Capsule::table($table)->select("tblorders.invoiceid")->where($table . ".id", $relatedId)->join("tblorders", $table . ".orderid", "=", "tblorders.id")->first();
        $runRenew = false;
        if (!is_null($orderInvoice) && $orderInvoice->invoiceid && $this->id != $orderInvoice->invoiceid) {
            $runRenew = true;
        }
        if (!$orderInvoice->invoiceid || $this->id == $orderInvoice->invoiceid) {
            $otherInvoice = Invoice\Item::where("type", $type)->where("relid", $relatedId)->where("invoiceid", "!=", $this->id)->where("invoiceid", "<", $this->id)->first();
            if ($otherInvoice) {
                $runRenew = true;
            }
            if (!$otherInvoice && $this->dateDue->toDateString() != $registrationDate) {
                $runRenew = true;
            }
        }
        return $runRenew;
    }
    public function vat()
    {
        return new Invoice\Tax\Vat($this);
    }
    public static function newInvoice($clientId, $gateway = NULL, $taxRate1 = NULL, $taxRate2 = NULL)
    {
        if ((!$gateway || is_null($taxRate1) || is_null($taxRate2)) && !function_exists("getClientsPaymentMethod")) {
            require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "clientfunctions.php";
        }
        if (!$gateway) {
            $gateway = getClientsPaymentMethod($clientId);
        }
        if (is_null($taxRate1) || is_null($taxRate2)) {
            $taxRate1 = 0;
            $taxRate2 = 0;
            if (\WHMCS\Config\Setting::getValue("TaxEnabled")) {
                $clientData = \WHMCS\Database\Capsule::table("tblclients")->where("tblclients.id", $clientId)->first(array("taxexempt", "tblclients.state", "tblclients.country"));
                if (!$clientData->taxexempt) {
                    if (!is_null($clientData->contact_country)) {
                        $taxCountry = $clientData->contact_country;
                        $taxState = $clientData->contact_state;
                    } else {
                        $taxCountry = $clientData->country;
                        $taxState = $clientData->state;
                    }
                    if (!function_exists("getTaxRate")) {
                        require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "invoicefunctions.php";
                    }
                    $taxLevel1 = getTaxRate(1, $taxState, $taxCountry);
                    $taxRate1 = $taxLevel1["rate"];
                    $taxLevel2 = getTaxRate(2, $taxState, $taxCountry);
                    $taxRate2 = $taxLevel2["rate"];
                }
            }
        }
        $invoice = new self();
        $invoice->dateCreated = \WHMCS\Carbon::now();
        $invoice->dateDue = \WHMCS\Carbon::now()->addDays((int) \WHMCS\Config\Setting::getValue("CreateInvoiceDaysBefore"));
        $invoice->clientId = $clientId;
        $invoice->status = self::STATUS_DRAFT;
        $invoice->paymentGateway = $gateway;
        $invoice->taxRate1 = $taxRate1;
        $invoice->taxRate2 = $taxRate2;
        return $invoice;
    }
    public function setStatusUnpaid()
    {
        $this->status = self::STATUS_UNPAID;
        return $this;
    }
    public function data()
    {
        return $this->hasOne("WHMCS\\Billing\\Invoice\\Data", "invoice_id");
    }
    public function transactionHistory()
    {
        return $this->hasMany("WHMCS\\Billing\\Payment\\Transaction\\History");
    }
    public function payMethod()
    {
        return $this->belongsTo("WHMCS\\Payment\\PayMethod\\Model", "paymethodid")->withTrashed();
    }
    public function getPayMethodRemoteToken()
    {
        $payment = null;
        if ($this->payMethod && !$this->payMethod->trashed() && $this->payMethod->payment instanceof \WHMCS\Payment\Contracts\RemoteTokenDetailsInterface) {
            $payment = $this->payMethod->payment;
        }
        $token = "";
        if ($payment) {
            $token = $payment->getRemoteToken();
        } else {
            $token = $this->client->paymentGatewayToken;
        }
        return $token;
    }
    public function setPayMethodRemoteToken($remoteToken)
    {
        $payment = null;
        if ($this->payMethod && !$this->payMethod->trashed()) {
            if ($this->payMethod->payment instanceof \WHMCS\Payment\Contracts\RemoteTokenDetailsInterface) {
                $payment = $this->payMethod->payment;
            } else {
                if ($this->payMethod->payment instanceof \WHMCS\Payment\PayMethod\Adapter\CreditCard) {
                    if ($remoteToken) {
                        $this->convertLocalCardToRemote($remoteToken);
                        return NULL;
                    }
                } else {
                    if ($this->payMethod->payment instanceof \WHMCS\Payment\PayMethod\Adapter\BankAccount && $remoteToken) {
                        $this->convertLocalBankAccountToRemote($remoteToken);
                        return NULL;
                    }
                }
            }
        }
        if ($payment) {
            if ($remoteToken) {
                $payment->setRemoteToken($remoteToken);
                $payment->save();
            } else {
                $this->payMethod->delete();
            }
        } else {
            $this->client->paymentGatewayToken = $remoteToken;
            $this->client->save();
        }
    }
    public function deletePayMethod()
    {
        if ($this->payMethod && !$this->payMethod->trashed()) {
            $this->payMethod->delete();
        }
    }
    public function convertLocalCardToRemote($remoteToken)
    {
        if (!$this->payMethod || $this->payMethod->trashed()) {
            $this->client->cardnum = "";
            $this->client->paymentGatewayToken = $remoteToken;
            $this->client->save();
        } else {
            if ($this->payMethod->payment instanceof \WHMCS\Payment\Contracts\RemoteTokenDetailsInterface) {
                $newPayment = $this->payMethod->payment;
                $newPayment->setRemoteToken($remoteToken);
                $newPayment->save();
            } else {
                if ($this->payMethod->payment instanceof \WHMCS\Payment\PayMethod\Adapter\CreditCard) {
                    $currentPayMethod = $this->payMethod;
                    $currentPayment = $currentPayMethod->payment;
                    if ($remoteToken) {
                        $newRemotePayMethod = \WHMCS\Payment\PayMethod\Adapter\RemoteCreditCard::factoryPayMethod($this->client, $currentPayMethod->contact, $currentPayMethod->getDescription());
                        if ($this->paymentGateway) {
                            $gateway = \WHMCS\Module\Gateway::factory($this->paymentGateway);
                            if ($gateway) {
                                $newRemotePayMethod->setGateway($gateway);
                            }
                        }
                        $newPayment = $newRemotePayMethod->payment;
                        $newPayment->setRemoteToken($remoteToken);
                        $newPayment->setCardNumber($currentPayment->getCardNumber());
                        $newPayment->setCardType($currentPayment->getCardType());
                        if ($currentPayment->getStartDate()) {
                            $newPayment->setStartDate($currentPayment->getStartDate());
                        }
                        if ($currentPayment->getExpiryDate()) {
                            $newPayment->setExpiryDate($currentPayment->getExpiryDate());
                        }
                        if ($currentPayment->getIssueNumber()) {
                            $newPayment->setIssueNumber($currentPayment->getIssueNumber());
                        }
                        $newPayment->save();
                        $newRemotePayMethod->save();
                        $this->payMethod()->associate($newRemotePayMethod);
                        $this->save();
                    }
                    $currentPayMethod->delete();
                }
            }
        }
    }
    public function convertLocalBankAccountToRemote($remoteToken)
    {
        if (!$this->payMethod || $this->payMethod->trashed()) {
            $this->client->storedBankAccountCrypt = "";
            $this->client->paymentGatewayToken = $remoteToken;
            $this->client->save();
        } else {
            if ($this->payMethod->payment instanceof \WHMCS\Payment\Contracts\RemoteTokenDetailsInterface) {
                $newPayment = $this->payMethod->payment;
                $newPayment->setRemoteToken($remoteToken);
                $newPayment->save();
            } else {
                if ($this->payMethod->payment instanceof \WHMCS\Payment\PayMethod\Adapter\BankAccount) {
                    $currentPayMethod = $this->payMethod;
                    $currentPayment = $currentPayMethod->payment;
                    if ($remoteToken) {
                        $newRemotePayMethod = \WHMCS\Payment\PayMethod\Adapter\RemoteBankAccount::factoryPayMethod($this->client, $currentPayMethod->contact, $currentPayMethod->getDescription());
                        if ($this->paymentGateway) {
                            $gateway = \WHMCS\Module\Gateway::factory($this->paymentGateway);
                            if ($gateway) {
                                $newRemotePayMethod->setGateway($gateway);
                            }
                        }
                        $newPayment = $newRemotePayMethod->payment;
                        $newPayment->setRemoteToken($remoteToken);
                        $newPayment->setName($currentPayment->getBankName());
                        $newPayment->save();
                        $newRemotePayMethod->save();
                        $this->payMethod()->associate($newRemotePayMethod);
                        $this->save();
                    }
                    $currentPayMethod->delete();
                }
            }
        }
    }
    public function saveRemoteCard($cardLastFour, $cardType, $expiryDate, $remoteToken)
    {
        if (!$remoteToken) {
            return NULL;
        }
        if ($cardLastFour && 4 < strlen($cardLastFour)) {
            $cardLastFour = substr($cardLastFour, -4);
        }
        $payMethod = null;
        if ($this->payMethod && !$this->payMethod->trashed() && $this->payMethod->payment instanceof \WHMCS\Payment\PayMethod\Adapter\RemoteCreditCard) {
            $payment = $this->payMethod->payment;
            if ($payment->getLastFour() === $cardLastFour && strcasecmp($payment->getCardType(), $cardType) === 0) {
                $payMethod = $this->payMethod;
            }
        }
        if (!$payMethod) {
            $payMethod = \WHMCS\Payment\PayMethod\Adapter\RemoteCreditCard::factoryPayMethod($this->client, $this->client, "New Card");
            if ($this->paymentGateway) {
                $gateway = \WHMCS\Module\Gateway::factory($this->paymentGateway);
                if ($gateway) {
                    $payMethod->setGateway($gateway);
                }
            }
            $payMethod->save();
        }
        $payment = $payMethod->payment;
        $payment->setLastFour($cardLastFour);
        if ($cardType) {
            $payment->setCardType($cardType);
        }
        $payment->setExpiryDate(\WHMCS\Carbon::createFromCcInput($expiryDate));
        $payment->setRemoteToken($remoteToken);
        $payment->save();
        $this->payMethod()->associate($payMethod);
        $this->save();
    }
}

?>