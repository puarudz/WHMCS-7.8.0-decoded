<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Log;

class Register extends \Illuminate\Database\Eloquent\Model implements RegisterInterface
{
    protected $table = "tbllog_register";
    protected static $unguarded = true;
    public function createTable($drop = false)
    {
        $schemaBuilder = \WHMCS\Database\Capsule::schema();
        if ($drop) {
            $schemaBuilder->dropIfExists($this->getTable());
        }
        if (!$schemaBuilder->hasTable($this->getTable())) {
            $schemaBuilder->create($this->getTable(), function ($table) {
                $table->increments("id");
                $table->string("name", 255)->default("");
                $table->integer("namespace_id")->unsigned()->nullable();
                $table->string("namespace", 255)->default("");
                $table->text("namespace_value")->default("");
                $table->timestamp("created_at")->default("0000-00-00 00:00:00");
                $table->timestamp("updated_at")->default("0000-00-00 00:00:00");
            });
        }
    }
    public function getName()
    {
        return $this->name;
    }
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    public function getNamespaceId()
    {
        return $this->namespace_id;
    }
    public function setNamespaceId($id)
    {
        $this->namespace_id = $id;
        return $this;
    }
    public function getNamespace()
    {
        return $this->namespace;
    }
    public function setNamespace($key)
    {
        $this->namespace = $key;
        return $this;
    }
    public function setValue($value)
    {
        $this->namespace_value = $value;
        return $this;
    }
    public function getValue()
    {
        return $this->namespace_value;
    }
    public function latestByNamespaces(array $namespaces, $id = NULL)
    {
        $table = $this->getTable();
        $query = static::where("created_at", function ($subquery) use($table, $namespaces, $id) {
            $subquery->from($table)->select("created_at")->whereIn("namespace", $namespaces)->orderBy("created_at", "desc")->take(1);
            if (!is_null($id)) {
                $subquery->where("namespace_id", $id);
            }
        })->whereIn("namespace", $namespaces);
        if (!is_null($id)) {
            $query->where("namespace_id", $id);
        }
        return $query->get();
    }
    public function sinceByNamespace(\WHMCS\Carbon $since, array $namespaces, $id = NULL)
    {
        $query = static::where("created_at", ">=", $since->toDateTimeString())->whereIn("namespace", $namespaces)->orderBy("created_at", "asc")->orderBy("id", "asc");
        if (!is_null($id)) {
            $query->where("namespace_id", $id);
        }
        return $query->get();
    }
    public function write($value)
    {
        $this->namespace_value = $value;
        return parent::save();
    }
}

?>