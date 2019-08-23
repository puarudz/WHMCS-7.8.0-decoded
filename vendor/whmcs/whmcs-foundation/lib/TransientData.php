<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class TransientData
{
    protected $chunkSize = 62000;
    const DB_TABLE = "tbltransientdata";
    public static function getInstance()
    {
        return new self();
    }
    public function store($name, $data, $life = 300)
    {
        if (!is_string($data)) {
            return false;
        }
        $expires = time() + (int) $life;
        if ($this->ifNameExists($name)) {
            $this->sqlUpdate($name, $data, $expires);
        } else {
            $this->sqlInsert($name, $data, $expires);
        }
        return true;
    }
    public function chunkedStore($name, $data, $life = 300)
    {
        if (!is_string($data)) {
            return false;
        }
        $expires = time() + (int) $life;
        $this->clearChunkedStorage($name);
        for ($i = 0; 0 < strlen($data); $i++) {
            $this->sqlInsert($name . ".chunk-" . $i, substr($data, 0, $this->chunkSize), $expires);
            $data = substr($data, $this->chunkSize);
        }
        return $this;
    }
    protected function clearChunkedStorage($name)
    {
        Database\Capsule::table("tbltransientdata")->where("name", "LIKE", $name . ".chunk-%")->delete();
    }
    public function retrieve($name)
    {
        return $this->sqlSelect($name, true);
    }
    public function retrieveChunkedItem($name)
    {
        $data = Database\Capsule::table("tbltransientdata")->where("name", "LIKE", $name . ".chunk-%")->where("expires", ">=", time())->pluck("data");
        if (0 < count($data)) {
            return implode($data);
        }
        return null;
    }
    public function retrieveByData($data)
    {
        return $this->sqlSelectByData($data, true);
    }
    public function ifNameExists($name)
    {
        $data = $this->sqlSelect($name);
        return $data === null ? false : true;
    }
    public function delete($name)
    {
        $this->sqlDelete($name);
        return true;
    }
    public function purgeExpired($delaySeconds = 120)
    {
        $now = time() - (int) $delaySeconds;
        return Database\Capsule::table("tbltransientdata")->where("expires", "<", $now)->delete();
    }
    protected function sqlSelect($name, $exclude_expired = false)
    {
        $lookup = Database\Capsule::table(self::DB_TABLE)->where("name", $name);
        if ($exclude_expired) {
            $lookup->where("expires", ">", time());
        }
        return $lookup->value("data");
    }
    protected function sqlSelectByData($data, $exclude_expired = false)
    {
        if ($exclude_expired) {
            $name = Database\Capsule::table("tbltransientdata")->where("data", "=", $data)->value("name");
        } else {
            $name = Database\Capsule::table("tbltransientdata")->where("data", "=", $data)->where("expires", ">", Carbon::now()->toDateString())->value("name");
        }
        return $name;
    }
    protected function sqlInsert($name, $data, $expires)
    {
        return Database\Capsule::table(self::DB_TABLE)->insertGetId(array("name" => $name, "data" => $data, "expires" => $expires));
    }
    protected function sqlUpdate($name, $data, $expires)
    {
        return Database\Capsule::table(self::DB_TABLE)->where("name", $name)->update(array("data" => $data, "expires" => $expires));
    }
    public function sqlDelete($name)
    {
        return Database\Capsule::table(self::DB_TABLE)->where("name", $name)->delete();
    }
}

?>