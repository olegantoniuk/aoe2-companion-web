<?php

namespace app\models;

/**
 * Reads JSON text columns as arrays: $model->listOf('steps').
 */
trait JsonFields
{
    public function listOf($attribute)
    {
        $v = $this->$attribute;
        if (is_array($v)) return $v;
        if ($v === null || $v === '') return [];
        $d = json_decode($v, true);
        return is_array($d) ? $d : [];
    }
}
