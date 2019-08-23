<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\PayMethod;

class Model extends \WHMCS\Model\AbstractModel implements \WHMCS\Payment\Contracts\PayMethodInterface
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use Traits\GatewayTrait;
    use Traits\PayMethodFromRequestTrait;
    use Traits\TypeTrait {
        getType as baseGetType;
    }
    protected $dates = array("deleted_at");
    protected $table = "tblpaymethods";
    public function createTable($drop = false)
    {
        $schemaBuilder = \WHMCS\Database\Capsule::schema();
        if ($drop) {
            $schemaBuilder->dropIfExists($this->getTable());
        }
        if (!$schemaBuilder->hasTable($this->getTable())) {
            $schemaBuilder->create($this->getTable(), function ($table) {
                $table->increments("id");
                $table->integer("userid")->default(0);
                $table->string("description", 255)->default("");
                $table->integer("contact_id")->default(0);
                $table->string("contact_type", 255)->default("");
                $table->integer("payment_id")->default(0);
                $table->string("payment_type", 255)->default("");
                $table->string("gateway_name", 255)->default("");
                $table->integer("order_preference")->default(0);
                $table->timestamp("created_at")->default("0000-00-00 00:00:00");
                $table->timestamp("updated_at")->default("0000-00-00 00:00:00");
                $table->softDeletes();
                $table->index("userid", "tblpaymethods_userid");
            });
        }
    }
    public static function boot()
    {
        parent::boot();
        static::addGlobalScope("orderPreference", function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->orderBy("order_preference", "asc");
        });
        self::deleting(function (Model $payMethod) {
            if ($payMethod->payment) {
                if ($payMethod->forceDeleting) {
                    $payMethod->payment->forceDelete();
                } else {
                    $payMethod->payment->delete();
                }
            }
        });
        self::deleted(function (Model $deletedPayMethod) {
            if ($deletedPayMethod->isDefaultPayMethod()) {
                $firstOtherPayMethod = $deletedPayMethod->newQuery()->where("userid", $deletedPayMethod->userid)->first();
                if ($firstOtherPayMethod) {
                    $firstOtherPayMethod->setAsDefaultPayMethod();
                }
            }
        });
    }
    public function newCollection(array $models = array())
    {
        return new Collection($models);
    }
    public function payment()
    {
        return $this->morphTo()->withTrashed();
    }
    public function client()
    {
        return $this->belongsTo("WHMCS\\User\\Client", "userid");
    }
    public function contact()
    {
        return $this->morphTo();
    }
    public function getType($instance = NULL)
    {
        $payment = $this->payment;
        if (!$payment) {
            throw new \RuntimeException("Missing payment details for determination of type");
        }
        return $this->baseGetType($payment);
    }
    public static function totalPayMethodsOnFile(\WHMCS\User\Contracts\UserInterface $client)
    {
        return self::query()->where("userid", $client->id)->count();
    }
    public function isDefaultPayMethod()
    {
        return $this->order_preference == 0;
    }
    public function setAsDefaultPayMethod()
    {
        if (!$this->isDefaultPayMethod()) {
            $all = $this->newQuery()->where("userid", $this->userid)->get();
            $i = 1;
            foreach ($all as $payMethod) {
                if ($payMethod->id == $this->id) {
                    $payMethod->order_preference = 0;
                    $payMethod->save();
                    continue;
                }
                if ($payMethod->order_preference != $i) {
                    $payMethod->order_preference = $i;
                    $payMethod->save();
                }
                $i++;
            }
            if ($this->relationLoaded("client") && $this->client->relationLoaded("payMethods")) {
                $relations = $this->client->getRelations();
                unset($relations["payMethod"]);
                $this->client->setRelations($relations);
            }
        }
        return $this;
    }
    public function getDescription()
    {
        return (string) $this->description;
    }
    public function setDescription($value)
    {
        $this->description = $value;
        return $this;
    }
    public function getGateway()
    {
        $gateway = null;
        $gatewayName = $this->gateway_name;
        if ($gatewayName) {
            $gateway = $this->loadGateway($gatewayName);
        }
        return $gateway;
    }
    public function setGateway(\WHMCS\Module\Gateway $value)
    {
        $this->gateway_name = (string) $value->getLoadedModule();
        return $this;
    }
    public function isUsingInactiveGateway()
    {
        $gateway = $this->getGateway();
        return $gateway && !$gateway->isLoadedModuleActive();
    }
    public function isExpired()
    {
        if ($this->isCreditCard()) {
            return $this->payment->isExpired();
        }
        return false;
    }
    public function getStatus()
    {
        if (defined("ADMINAREA")) {
            $active = \AdminLang::trans("status.active");
            $expired = \AdminLang::trans("status.expired");
        } else {
            if (defined("CLIENTAREA")) {
                $active = \Lang::trans("clientareaactive");
                $expired = \Lang::trans("clientareaexpired");
            } else {
                $active = "Active";
                $expired = "Expired";
            }
        }
        return $this->isExpired() ? $expired : $active;
    }
    public function getFontAwesomeIcon()
    {
        if ($this->isCreditCard()) {
            switch ($this->payment->card_type) {
                case "Visa":
                    return "fab fa-cc-visa";
                case "MasterCard":
                    return "fab fa-cc-mastercard";
                case "American Express":
                    return "fab fa-cc-amex";
                case "Discover":
                    return "fab fa-cc-discover";
                case "JCB":
                    return "fab fa-cc-jcb";
                case "Diners":
                    return "fab fa-cc-diners-club";
                default:
                    return "fal fa-credit-card";
            }
        } else {
            return "fas fa-university";
        }
    }
    public function getContactId()
    {
        if ($this->contact_type == "Contact") {
            return $this->contact_id;
        }
        return 0;
    }
    public function isTokenised()
    {
        return $this->payment instanceof \WHMCS\Payment\Contracts\RemoteTokenDetailsInterface;
    }
    public function getPaymentDescription()
    {
        return $this->payment->getDisplayName();
    }
    public function capture(\WHMCS\Billing\Invoice $invoice, $cvc = "")
    {
        if (!function_exists("captureCCPayment")) {
            require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "ccfunctions.php";
        }
        return captureCCPayment($invoice->id, $cvc, array(), $this);
    }
    public static function findForClient($id, $clientId)
    {
        return parent::where("id", $id)->where("userid", $clientId)->first();
    }
    public function invoices()
    {
        return $this->hasMany("WHMCS\\Billing\\Invoice", "paymethodid", "id");
    }
    private static function deleteCreditCardsByType(array $payMethodTypes)
    {
        $placeholders = implode(",", array_fill(0, count($payMethodTypes), "?"));
        \WHMCS\Database\Capsule::update("UPDATE tblpaymethods INNER JOIN tblcreditcards " . "ON tblpaymethods.payment_id=tblcreditcards.id " . "SET tblpaymethods.deleted_at=NOW(),tblcreditcards.deleted_at=NOW(),tblcreditcards.card_data=\"\" " . "WHERE tblpaymethods.payment_type IN (" . $placeholders . ") ", $payMethodTypes);
    }
    public static function deleteLocalCreditCards()
    {
        static::deleteCreditCardsByType(array(\WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_CREDITCARD_LOCAL));
    }
}

?>