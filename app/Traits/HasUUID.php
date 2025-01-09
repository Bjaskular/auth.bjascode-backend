<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUUID
{
    public static function booted() {
        static::creating(function (Model $model) {
            $model->{$model->getKeyName()} = (string) Str::orderedUuid();
        });
    }

    public function getIncrementing()
    {
        return false;
    }

    public function getKeyType()
    {
        return 'string';
    }
}
