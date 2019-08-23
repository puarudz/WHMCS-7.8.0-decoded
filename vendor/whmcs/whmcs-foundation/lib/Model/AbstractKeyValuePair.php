<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Model;

abstract class AbstractKeyValuePair extends AbstractModel
{
    protected $booleanValues = array();
    protected $nonEmptyValues = array();
    protected $semanticVersionValues = array();
    protected $commaSeparatedValues = array();
    protected $characterSeparatedValues = array();
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
        $this->incrementing = false;
    }
    public function setAttribute($key, $value)
    {
        if ($key != $this->primaryKey && $key != static::UPDATED_AT && $key != static::CREATED_AT) {
            if (in_array($this->{$this->primaryKey}, $this->nonEmptyValues) && ($value == "" || is_null($value))) {
                $class = get_called_class();
                throw new \WHMCS\Exception\Model\EmptyValue("The \"" . $class . "\" key \"" . $this->{$this->primaryKey} . "\" value cannot not be empty.");
            }
            if (in_array($this->{$this->primaryKey}, $this->booleanValues)) {
                $value = $this->fromBoolean($value);
            } else {
                if (in_array($this->{$this->primaryKey}, $this->semanticVersionValues)) {
                    $value = $this->fromSemanticVersion($value);
                } else {
                    if (in_array($this->{$this->primaryKey}, $this->commaSeparatedValues)) {
                        $value = $this->fromArrayToCharacterSeparatedValue($value);
                    } else {
                        foreach ($this->characterSeparatedValues as $character => $columns) {
                            if (in_array($this->{$this->primaryKey}, $columns)) {
                                $value = $this->fromArrayToCharacterSeparatedValue($value, $character);
                                break;
                            }
                        }
                    }
                }
            }
        }
        return parent::setAttribute($key, $value);
    }
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);
        if ($key != $this->primaryKey && $key != static::UPDATED_AT && $key != static::CREATED_AT) {
            if (in_array($this->{$this->primaryKey}, $this->booleanValues)) {
                $value = $this->asBoolean($value);
            } else {
                if (in_array($this->{$this->primaryKey}, $this->semanticVersionValues)) {
                    $value = $this->asSemanticVersion($value);
                } else {
                    if (in_array($this->{$this->primaryKey}, $this->commaSeparatedValues)) {
                        $value = $this->asArrayFromCharacterSeparatedValue($value);
                    } else {
                        foreach ($this->characterSeparatedValues as $character => $columns) {
                            if (in_array($this->{$this->primaryKey}, $columns)) {
                                $value = $this->asArrayFromCharacterSeparatedValue($value, $character);
                                break;
                            }
                        }
                    }
                }
            }
        }
        return $value;
    }
    public function isCommaSeparatedValue()
    {
        return in_array($this->{$this->primaryKey}, $this->commaSeparatedValues);
    }
}

?>