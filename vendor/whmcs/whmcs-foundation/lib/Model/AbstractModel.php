<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Model;

class AbstractModel extends \Illuminate\Database\Eloquent\Model implements Contracts\ModelInterface
{
    public $unique = array();
    public $guardedForUpdate = array();
    protected $columnMap = array();
    protected $booleans = array();
    protected $strings = array();
    protected $ints = array();
    protected $semanticVersions = array();
    protected $commaSeparated = array();
    protected $characterSeparated = array();
    protected static $tableColumnCache = array();
    protected $rules = array();
    protected $errors = NULL;
    protected $customValidationMessages = array();
    public static function boot()
    {
        parent::boot();
        self::observe(new Observer());
    }
    public function clearColumnCache()
    {
        if (isset(static::$tableColumnCache[$this->table])) {
            unset(static::$tableColumnCache[$this->table]);
        }
        return $this;
    }
    protected function hasColumn($column)
    {
        if (!isset(static::$tableColumnCache[$this->table])) {
            static::$tableColumnCache[$this->table] = array("columns" => array(), "not-exists" => array());
        }
        if (in_array($column, static::$tableColumnCache[$this->table]["columns"])) {
            return true;
        }
        if (in_array($column, static::$tableColumnCache[$this->table]["not-exists"])) {
            return false;
        }
        static::$tableColumnCache[$this->table]["columns"] = array_map("strtolower", \Illuminate\Database\Capsule\Manager::schema()->getColumnListing($this->table));
        if (in_array($column, static::$tableColumnCache[$this->table]["columns"])) {
            return true;
        }
        static::$tableColumnCache[$this->table]["not-exists"][] = $column;
        return false;
    }
    public function getAttribute($key)
    {
        $originalKey = $key;
        $isColumnMapped = array_key_exists($key, $this->columnMap);
        if ($isColumnMapped) {
            $key = $this->columnMap[$key];
        }
        if ($isColumnMapped && in_array($originalKey, $this->getDates())) {
            $dateValue = parent::getAttribute($key);
            if (in_array($dateValue, array("0000-00-00 00:00:00", "0000-00-00")) || empty($dateValue)) {
                $value = \WHMCS\Carbon::createFromTimestamp(0, "UTC");
            } else {
                $value = $this->asDateTime($dateValue);
            }
        } else {
            $value = parent::getAttribute($key);
        }
        if (is_null($value)) {
            $value = parent::getAttribute(snake_case($key));
        }
        if (is_null($value)) {
            $value = parent::getAttribute(strtolower($key));
        }
        if ($isColumnMapped && $this->hasGetMutator($originalKey)) {
            $value = $this->mutateAttribute($originalKey, $value);
        }
        $isBoolean = $this->isBooleanColumn($originalKey) || $this->isBooleanColumn($key);
        $isSemanticVersion = $this->isSemanticVersionColumn($originalKey) || $this->isSemanticVersionColumn($key);
        $isCommaSeparated = $this->isCommaSeparatedColumn($originalKey) || $this->isCommaSeparatedColumn($key);
        if ($isBoolean) {
            $value = $this->asBoolean($value);
        } else {
            if ($isSemanticVersion) {
                $value = $this->asSemanticVersion($value);
            } else {
                if ($isCommaSeparated) {
                    $value = $this->asArrayFromCharacterSeparatedValue($value);
                } else {
                    foreach ($this->characterSeparated as $character => $columns) {
                        if (in_array($originalKey, $columns) || in_array($key, $columns)) {
                            $value = $this->asArrayFromCharacterSeparatedValue($value, $character);
                            break;
                        }
                    }
                }
            }
        }
        return $value;
    }
    public function isAttributeSet($key)
    {
        return isset($this->attributes[$key]) || isset($this->relations[$key]) || $this->hasGetMutator($key) && !is_null($this->getAttributeValue($key));
    }
    public function setAttribute($key, $value)
    {
        $originalKey = $key;
        if (!$this->isAttributeSet($key)) {
            if ($this->isAttributeSet(snake_case($key)) || $this->hasColumn(snake_case($key))) {
                $key = snake_case($key);
            } else {
                if (array_key_exists($key, $this->columnMap)) {
                    $key = $this->columnMap[$key];
                } else {
                    $key = strtolower($key);
                }
            }
        }
        if (in_array($originalKey, $this->booleans)) {
            $value = $this->fromBoolean($value);
        } else {
            if (in_array($originalKey, $this->strings)) {
                $value = $this->fromString($value);
            } else {
                if (in_array($originalKey, $this->ints)) {
                    $value = $this->fromInt($value);
                } else {
                    if (in_array($originalKey, $this->semanticVersions)) {
                        $value = $this->fromSemanticVersion($value);
                    } else {
                        if (in_array($originalKey, $this->commaSeparated)) {
                            $value = $this->fromArrayToCharacterSeparatedValue($value);
                        } else {
                            foreach ($this->characterSeparated as $character => $columns) {
                                if (in_array($originalKey, $columns)) {
                                    $value = $this->fromArrayToCharacterSeparatedValue($value, $character);
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        return parent::setAttribute($key, $value);
    }
    public function getRawAttribute($key = NULL, $default = NULL)
    {
        return \Illuminate\Support\Arr::get($this->attributes, $key, $default);
    }
    public function fromBoolean($value)
    {
        return (int) (bool) $value;
    }
    public function fromString($value)
    {
        return (string) $value;
    }
    public function fromInt($value)
    {
        return (int) $value;
    }
    public function asBoolean($value)
    {
        return (bool) $value;
    }
    public function asArrayFromCharacterSeparatedValue($data = "", $character = ",")
    {
        return array_values(array_filter(array_map(function ($item) {
            $item = trim($item);
            return strlen($item) ? $item : null;
        }, explode($character, $data))));
    }
    public function fromArrayToCharacterSeparatedValue(array $list = array(), $character = ",")
    {
        $data = implode($character, array_map("trim", $list));
        $data = str_replace($character . $character, $character, $data);
        $data = trim($data, $character);
        return $data;
    }
    public function fromSemanticVersion(\WHMCS\Version\SemanticVersion $version)
    {
        return $version->getCanonical();
    }
    public function asSemanticVersion($version)
    {
        return new \WHMCS\Version\SemanticVersion($version);
    }
    public static function convertBoolean($value)
    {
        if (!$value || is_string($value) && ($value == "off" || $value == "")) {
            return false;
        }
        return true;
    }
    public static function convertBooleanColumn($column)
    {
        $class = get_called_class();
        $object = new $class();
        $table = $object->getTable();
        \Illuminate\Database\Capsule\Manager::table($table)->where($column, "off")->update(array($column => ""));
        \Illuminate\Database\Capsule\Manager::table($table)->where($column, "!=", "")->where($column, "!=", "0")->update(array($column => 1));
        \Illuminate\Database\Capsule\Manager::table($table)->where($column, "")->update(array($column => 0));
        \Illuminate\Database\Capsule\Manager::connection()->getPdo()->exec("alter table `" . $table . "` change `" . $column . "` `" . $column . "` tinyint(1) not null");
    }
    public static function convertUnixTimestampIntegerToTimestampColumn($column)
    {
        $class = get_called_class();
        $object = new $class();
        $tableName = $object->getTable();
        $tempColumn = (string) $column . "_temp";
        \Illuminate\Database\Capsule\Manager::schema()->table($tableName, function ($table) use($tempColumn) {
            $table->timestamp($tempColumn);
        });
        $pdo = \Illuminate\Database\Capsule\Manager::connection()->getPdo();
        $statement = $pdo->prepare("update `" . $tableName . "` set `" . $tempColumn . "` = FROM_UNIXTIME(" . $column . ")");
        $statement->execute();
        \Illuminate\Database\Capsule\Manager::schema()->table($tableName, function ($table) use($column) {
            $table->dropColumn($column);
        });
        \Illuminate\Database\Capsule\Manager::schema()->table($tableName, function () use($tableName, $column, $tempColumn) {
            $pdo = \Illuminate\Database\Capsule\Manager::connection()->getPdo();
            $statement = $pdo->prepare("alter table `" . $tableName . "`" . " change `" . $tempColumn . "`" . " `" . $column . "` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'");
            $statement->execute();
        });
    }
    protected function isBooleanColumn($column)
    {
        return in_array($column, $this->booleans);
    }
    protected function isSemanticVersionColumn($column)
    {
        return in_array($column, $this->semanticVersions);
    }
    protected function isCommaSeparatedColumn($column)
    {
        return in_array($column, $this->commaSeparated);
    }
    protected function decryptValue($cipherText, $key)
    {
        return \Illuminate\Database\Capsule\Manager::connection()->selectOne("select AES_DECRYPT(?, ?) as decrypted", array($cipherText, $key))->decrypted;
    }
    protected function encryptValue($text, $key)
    {
        return \Illuminate\Database\Capsule\Manager::connection()->selectOne("select AES_ENCRYPT(?, ?) as encrypted", array($text, $key))->encrypted;
    }
    protected function aesEncryptValue($text, $key)
    {
        $encryption = new \WHMCS\Security\Encryption\Aes();
        $encryption->setKey($key);
        return $encryption->encrypt($text);
    }
    protected function aesDecryptValue($text, $key)
    {
        $encryption = new \WHMCS\Security\Encryption\Aes();
        $encryption->setKey($key);
        return $encryption->decrypt($text);
    }
    protected function serializeDate(\DateTime $date)
    {
        if ((int) (string) $date < 0) {
            return "0000-00-00 00:00:00";
        }
        return $date->format($this->getDateFormat());
    }
    protected function decrypt($value)
    {
        return decrypt($value);
    }
    protected function encrypt($value)
    {
        return encrypt($value);
    }
    protected function asDateTime($value)
    {
        if ($value instanceof \WHMCS\Carbon) {
            return $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return new \WHMCS\Carbon($value->format("Y-m-d H:i:s.u"), $value->getTimeZone());
        }
        if (is_numeric($value)) {
            return \WHMCS\Carbon::createFromTimestamp($value);
        }
        if (preg_match("/^(\\d{4})-(\\d{1,2})-(\\d{1,2})\$/", $value)) {
            return \WHMCS\Carbon::createFromFormat("Y-m-d", $value)->startOfDay();
        }
        return \WHMCS\Carbon::createFromFormat($this->getDateFormat(), $value);
    }
    public function fromDateTime($value)
    {
        $format = $this->getDateFormat();
        $value = parent::asDateTime($value);
        return $value->format($format);
    }
    public function toArrayUsingColumnMapNames()
    {
        $data = $this->toArray();
        if (0 < count($this->columnMap)) {
            $keys = array_keys($data);
            foreach ($this->columnMap as $mappedName => $dbFieldName) {
                if (array_key_exists($dbFieldName, $data) && !in_array($dbFieldName, $this->hidden)) {
                    $keys[array_search($dbFieldName, $keys)] = $mappedName;
                }
            }
            $keys = array_map(function ($item) {
                return in_array($item, array("created_at", "updated_at")) ? \Illuminate\Support\Str::camel($item) : $item;
            }, $keys);
            $data = array_combine($keys, $data);
        }
        return $data;
    }
    public function validate()
    {
        $translator = defined("ADMINAREA") ? \AdminLang::self() : \Lang::self();
        $validator = new \Illuminate\Validation\Validator($translator, $this->attributes, $this->rules, $this->customValidationMessages);
        if ($validator->passes()) {
            return true;
        }
        $this->errors = $validator->messages();
        return false;
    }
    public function setCustomValidationMessages(array $messages)
    {
        $this->customValidationMessages = $messages;
        return $this;
    }
    public function errors()
    {
        return $this->errors;
    }
    public function getCustomFieldValuesAttribute()
    {
        $customFieldValues = $this->getRelationValue("customFieldValues");
        if (!$this instanceof \WHMCS\CustomField) {
            $customFieldType = $this->getCustomFieldType();
            $customFieldRelId = $this->getCustomFieldRelId();
            if (!is_null($customFieldType) && !is_null($customFieldRelId)) {
                $customFieldValues = $customFieldValues->filter(function (\WHMCS\CustomField\CustomFieldValue $customFieldValue) use($customFieldType, $customFieldRelId) {
                    return $customFieldValue->customField->type === $customFieldType && $customFieldValue->customField->relatedId === $customFieldRelId;
                });
            } else {
                throw new \WHMCS\Exception("A model that supports custom fields must implement" . " getCustomFieldRelId() and getCustomFieldType() methods");
            }
        }
        return $customFieldValues;
    }
    protected function getCustomFieldType()
    {
        return null;
    }
    protected function getCustomFieldRelId()
    {
        return null;
    }
}

?>