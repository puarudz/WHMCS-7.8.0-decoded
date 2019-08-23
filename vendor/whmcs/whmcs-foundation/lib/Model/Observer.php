<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Model;

class Observer
{
    public function creating(\Illuminate\Database\Eloquent\Model $model)
    {
        $this->enforceUniqueConstraint($model);
    }
    public function updating(\Illuminate\Database\Eloquent\Model $model)
    {
        $this->enforceUniqueConstraint($model);
        $this->enforceGuardedForUpdateProperties($model);
    }
    protected function enforceUniqueConstraint(\Illuminate\Database\Eloquent\Model $model)
    {
        $class = get_class($model);
        foreach ($model->unique as $property) {
            if ($model->isDirty($property) && 0 < $class::where($property, "=", $model->{$property})->count()) {
                throw new \WHMCS\Exception\Model\UniqueConstraint("A \"" . $class . "\" record with \"" . $property . "\" value \"" . $model->{$property} . "\" already exists.");
            }
        }
    }
    protected function enforceGuardedForUpdateProperties(\Illuminate\Database\Eloquent\Model $model)
    {
        $class = get_class($model);
        foreach ($model->guardedForUpdate as $property) {
            if ($model->isDirty($property)) {
                throw new \WHMCS\Exception\Model\GuardedForUpdate("The \"" . $class . "\" record \"" . $property . "\" property is guarded against updates.");
            }
        }
    }
}

?>