<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCSProjectManagement;

class Invoices extends BaseProjectEntity
{
    public function get()
    {
        $invoices = array();
        $otherInvoices = \WHMCS\Billing\Invoice\Item::where(function (\Illuminate\Database\Eloquent\Builder $query) {
            $query->where("description", "like", "%Project #" . $this->project->id . "%");
            foreach ($this->project->ticketids as $ticketId) {
                $query->orWhere("description", "like", "%Ticket #" . $ticketId . "%");
            }
        });
        if ($this->project->invoiceids) {
            $otherInvoices->whereNotIn("invoiceid", $this->project->invoiceids);
        }
        $otherInvoices = $otherInvoices->pluck("invoiceid")->toArray();
        if ($otherInvoices) {
            $this->project->invoiceids = array_merge($this->project->invoiceids, $otherInvoices);
            $this->project->save();
        }
        foreach ($this->project->invoiceids as $key => $invoiceId) {
            try {
                $invoice = $this->getInvoiceById($invoiceId);
                $invoiceArray = $invoice->toArray();
                $invoiceArray["balance"] = $invoice->balance;
                if ($invoiceArray["datepaid"] == "-0001-11-30 00:00:00") {
                    $invoiceArray["datepaid"] = "0000-00-00 00:00:00";
                }
                $invoiceArray["currencyId"] = $invoice->client->currencyId;
                $invoices[] = $invoiceArray;
            } catch (\Exception $e) {
                unset($this->project->invoiceids[$key]);
                $this->project->save();
            }
        }
        return $invoices;
    }
    public function search()
    {
        $search = \App::getFromRequest("search");
        $invoiceBuilder = Models\WHMCSInvoice::where(function (\Illuminate\Database\Eloquent\Builder $query) use($search) {
            $query->where("id", "like", $search . "%");
            if ($this->project->userid) {
                $query->orWhere("userid", "=", $this->project->userid);
            }
        });
        if ($this->project->invoiceids) {
            $invoiceBuilder->whereNotIn("id", $this->project->invoiceids);
        }
        $invoices = $invoiceBuilder->get();
        $return = array();
        foreach ($invoices as $invoice) {
            $thisInvoice = new \stdClass();
            $thisInvoice->id = $invoice->id;
            $thisInvoice->invoiceNumber = $invoice->invoiceNumber;
            $thisInvoice->dateCreated = fromMySQLDate($invoice->dateCreated);
            $thisInvoice->dateDue = fromMySQLDate($invoice->dateDue);
            $thisInvoice->total = (string) formatCurrency($invoice->total, $invoice->client->currencyId);
            $thisInvoice->balance = (string) formatCurrency($invoice->balance, $invoice->client->currencyId);
            $thisInvoice->status = $invoice->status;
            $return[] = $thisInvoice;
        }
        return array("invoices" => $return);
    }
    public function associate()
    {
        $invoiceId = \App::getFromRequest("invoice");
        if (!$invoiceId) {
            throw new Exception("Invoice ID is required");
        }
        if (in_array($invoiceId, $this->project->invoiceids)) {
            throw new Exception("This invoice is already associated with this project");
        }
        $invoice = Models\WHMCSInvoice::findOrFail($invoiceId);
        $currentInvoiceList = $this->invoiceLinks($this->project->invoiceids);
        $this->project->invoiceids[] = $invoice->id;
        $this->project->save();
        $this->project->log()->add("Invoice Associated: Invoice #" . $invoice->id);
        $returnInvoice = $invoice->toArray();
        $returnInvoice["dateCreated"] = fromMySQLDate($invoice->dateCreated);
        $returnInvoice["dateDue"] = fromMySQLDate($invoice->dateDue);
        $returnInvoice["total"] = (string) formatCurrency($invoice->total, $invoice->client->currencyId);
        $returnInvoice["balance"] = (string) formatCurrency($invoice->balance, $invoice->client->currencyId);
        $newInvoiceList = $this->invoiceLinks($this->project->invoiceids);
        $projectChanges = array(array("field" => "Invoice Added", "oldValue" => implode(", ", $currentInvoiceList), "newValue" => implode(", ", $newInvoiceList)));
        $this->project->notify()->staff($projectChanges);
        return array("invoice" => $returnInvoice, "invoiceCount" => count($this->project->invoiceids));
    }
    public function create()
    {
        if (!$this->project->userid) {
            throw new Exception("Cannot create Invoice without associated Client");
        }
        $paymentMethod = getClientsPaymentMethod($this->project->userid);
        if (!$paymentMethod) {
            throw new Exception("There are no active Payment Gateways. Please enable a Payment Gateway and try again");
        }
        $description = \App::getFromRequest("description");
        $amount = \App::getFromRequest("amount");
        $created = \App::getFromRequest("created") ?: getTodaysDate();
        $due = \App::getFromRequest("due") ?: getTodaysDate();
        $sendEmail = \App::getFromRequest("sendEmail");
        $applyTax = \App::getFromRequest("applyTax");
        $invoiceDetails = localAPI("createinvoice", array("sendinvoice" => $sendEmail, "paymentmethod" => $paymentMethod, "status" => "Unpaid", "userid" => $this->project->userid, "itemdescription[]" => $description, "itemamount[]" => $amount, "itemtaxed[]" => (int) (bool) $applyTax, "date" => toMySQLDate($created), "duedate" => toMySQLDate($due)));
        if ($invoiceDetails["result"] != "success") {
            throw new Exception($invoiceDetails["message"]);
        }
        $currentInvoiceList = $this->invoiceLinks($this->project->invoiceids);
        $this->project->invoiceids[] = $invoiceDetails["invoiceid"];
        $this->project->save();
        $this->project->log()->add("Invoice Created: Invoice #" . $invoiceDetails["invoiceid"]);
        $newInvoiceList = $this->invoiceLinks($this->project->invoiceids);
        $projectChanges = array(array("field" => "Invoice Created", "oldValue" => implode(", ", $currentInvoiceList), "newValue" => implode(", ", $newInvoiceList)));
        $this->project->notify()->staff($projectChanges);
        $invoice = Models\WHMCSInvoice::findOrFail($invoiceDetails["invoiceid"]);
        $returnInvoice = $invoice->toArray();
        $returnInvoice["dateCreated"] = fromMySQLDate($invoice->dateCreated);
        $returnInvoice["dateDue"] = fromMySQLDate($invoice->dateDue);
        $returnInvoice["total"] = formatCurrency($invoice->total, $invoice->client->currencyId);
        $returnInvoice["balance"] = formatCurrency($invoice->balance, $invoice->client->currencyId);
        if (is_object($returnInvoice["total"])) {
            $returnInvoice["total"] = $returnInvoice["total"]->toFull();
        }
        if (is_object($returnInvoice["balance"])) {
            $returnInvoice["balance"] = $returnInvoice["balance"]->toFull();
        }
        return array("invoice" => $returnInvoice, "invoiceCount" => count($this->project->invoiceids));
    }
    public function unlink()
    {
        $invoiceId = \App::getFromRequest("invoice");
        if (!$invoiceId) {
            throw new Exception("No Invoice Supplied");
        }
        if (!in_array($invoiceId, $this->project->invoiceids)) {
            throw new Exception("Invoice not associated with Project");
        }
        $currentInvoiceList = $this->invoiceLinks($this->project->invoiceids);
        $invoices = array_flip($this->project->invoiceids);
        unset($invoices[$invoiceId]);
        $this->project->invoiceids = array_flip($invoices);
        $this->project->save();
        $this->project->log()->add("Invoice Unlinked: Invoice #" . $invoiceId);
        $newInvoiceList = $this->invoiceLinks($this->project->invoiceids);
        $projectChanges = array(array("field" => "Invoice Unlinked", "oldValue" => implode(", ", $currentInvoiceList), "newValue" => implode(", ", $newInvoiceList)));
        $this->project->notify()->staff($projectChanges);
        return array("invoiceId" => $invoiceId, "invoiceCount" => count($this->project->invoiceids));
    }
    protected function getInvoiceById($invoiceId)
    {
        return Models\WHMCSInvoice::with("client")->findOrFail($invoiceId);
    }
    public function getSingleInvoiceById($invoiceId)
    {
        return $this->getInvoiceById($invoiceId)->toArray();
    }
    public function invoiceLinks(array $invoiceIds)
    {
        $systemUrl = \App::getSystemURL();
        $adminFolder = \App::get_admin_folder_name();
        $invoiceList = array();
        foreach ($invoiceIds as $invoiceId) {
            if ($invoiceId) {
                $invoiceLink = $systemUrl . $adminFolder . DIRECTORY_SEPARATOR . "invoices.php?action=edit&id=" . $invoiceId;
                $invoiceList[] = "<a href=\"" . $invoiceLink . "\">" . "#" . $invoiceId . "</a>";
            }
        }
        return $invoiceList;
    }
}

?>