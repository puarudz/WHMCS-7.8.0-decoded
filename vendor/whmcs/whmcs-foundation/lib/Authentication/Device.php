<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Authentication;

class Device extends \WHMCS\Model\AbstractModel
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $table = "tbldeviceauth";
    protected $primaryKey = "id";
    protected $permissions = NULL;
    protected $fillable = array("identifier", "secret", "compat_secret", "user_id", "is_admin", "role_ids", "description");
    protected $booleans = array("is_admin");
    protected $dates = array("last_access");
    protected $casts = array("role_ids" => "json");
    public function createTable($drop = false)
    {
        $schemaBuilder = \WHMCS\Database\Capsule::schema();
        if ($drop) {
            $schemaBuilder->dropIfExists($this->getTable());
        }
        if (!$schemaBuilder->hasTable($this->getTable())) {
            $schemaBuilder->create($this->getTable(), function ($table) {
                $table->increments("id");
                $table->string("identifier", 255)->unique()->default("");
                $table->string("secret", 255)->default("");
                $table->string("compat_secret", 255)->default("");
                $table->integer("user_id")->default(0);
                $table->boolean("is_admin")->default(false);
                $table->text("role_ids");
                $table->string("description", 255)->default("");
                $table->timestamp("last_access")->default("0000-00-00 00:00:00");
                $table->timestamp("created_at")->default("0000-00-00 00:00:00");
                $table->timestamp("updated_at")->default("0000-00-00 00:00:00");
                $table->softDeletes();
            });
        }
    }
    public static function boot()
    {
        parent::boot();
        static::deleted(function ($model) {
            if ($model->exists && $model->secret) {
                $model->update(array("secret" => "", "compat_secret" => ""));
            }
        });
        static::saving(function ($model) {
            $secret = $model->secret;
            $hasher = new \WHMCS\Security\Hash\Password();
            $hashInfo = $hasher->getInfo($secret);
            if ($hashInfo["algoName"] != \WHMCS\Security\Hash\Password::HASH_BCRYPT) {
                $model->secret = $hasher->hash($secret);
                $model->compat_secret = $hasher->hash(md5($secret));
            }
        });
    }
    public function scopeByIdentifier($query, $identifier)
    {
        return $query->where("identifier", "=", $identifier);
    }
    public function admin()
    {
        if (!$this->is_admin) {
            throw new \RuntimeException("Device identity not associate with an admin user");
        }
        return $this->belongsTo("\\WHMCS\\User\\Admin", "user_id");
    }
    public static function newAdminDevice(\WHMCS\User\Admin $admin, $description = "")
    {
        $secret = static::generateSecret();
        $attributes = array("identifier" => static::generateIdentifier(), "secret" => $secret, "user_id" => $admin->id, "is_admin" => 1, "description" => (string) $description);
        return new static($attributes);
    }
    public function verify($userInput)
    {
        if (!$this->secret) {
            return false;
        }
        $hasher = new \WHMCS\Security\Hash\Password();
        return $hasher->verify($userInput, $this->secret);
    }
    public function verifyCompat($userInput)
    {
        if (!$this->compat_secret) {
            return false;
        }
        $hasher = new \WHMCS\Security\Hash\Password();
        return $hasher->verify($userInput, $this->compat_secret);
    }
    public static function generateIdentifier()
    {
        return str_random(32);
    }
    public static function generateSecret()
    {
        return str_random(32);
    }
    public function rolesCollection()
    {
        $roleIds = $this->role_ids;
        if (!is_array($roleIds)) {
            $roleIds = array();
        }
        $collection = array();
        foreach ($roleIds as $id) {
            $apiRole = \WHMCS\Api\Authorization\ApiRole::find($id);
            if (!is_null($apiRole)) {
                $collection[$id] = $apiRole;
            }
        }
        return $collection;
    }
    public function permissions()
    {
        if (!$this->permissions || $this->isDirty("role_ids")) {
            $roles = $this->rolesCollection();
            $aggregateRolePermissions = new \WHMCS\Authorization\Rbac\AccessList($roles);
            $this->permissions = $aggregateRolePermissions;
        }
        return $this->permissions;
    }
    public function addRole(\WHMCS\Authorization\Contracts\RoleInterface $role)
    {
        $currentRoles = $this->role_ids;
        if (!is_array($currentRoles)) {
            $currentRoles = array();
        }
        $roleId = (int) $role->getId();
        if (!in_array($roleId, $currentRoles)) {
            array_push($currentRoles, $roleId);
            $this->role_ids = array_filter($currentRoles);
        }
        return $this;
    }
    public function removeRole(\WHMCS\Authorization\Contracts\RoleInterface $role)
    {
        $this->role_ids = array_diff($this->role_ids, array((int) $role->getId()));
        return $this;
    }
    public static function purgeRoleFromAllDevices(\WHMCS\Authorization\Contracts\RoleInterface $role)
    {
        $roleId = $role->getId();
        $devices = Device::Where("role_ids", "=", "[" . $roleId . "]")->orWhere("role_ids", "like", "[" . $roleId . ",%")->orWhere("role_ids", "like", "%," . $roleId . ",%")->orWhere("role_ids", "like", "%," . $roleId . "]")->get();
        $updated = array();
        foreach ($devices as $device) {
            if (in_array($roleId, $device->role_ids)) {
                $device->removeRole($role);
                $device->save();
                $updated[] = $device->identifier;
            }
        }
        if ($updated) {
            $msg = sprintf("Removed role \"%d%s\" from API identifiers \"%s\"", $roleId, $role->role ? ": " . $role->role : "", implode(", ", $updated));
            logActivity($msg);
        }
    }
}

?>