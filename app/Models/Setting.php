<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'autoload'];

    // se salvi JSON, usa accessors/mutators per cast automatico
    public function getValueAttribute($value) {
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    public function setValueAttribute($value) {
        // se array o object lo salvo JSON, altrimenti stringa
        $this->attributes['value'] = (is_array($value) || is_object($value)) ? json_encode($value) : (string) $value;
    }
}
