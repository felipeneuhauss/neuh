<?php

namespace Neuh\Traits;

use Ramsey\Uuid\Uuid;

/**
 * Trait UuidModel
 * @package App\Models\Database\Traits
 */
trait UuidModel
{

    /**
     * Binds creating/saving events to create UUIDs (and also prevent them from being overwritten).
     *
     * @return void
     */
    public static function bootUuidModel()
    {
        static::creating(function ($model) {
            $keyName = $model->getKeyName();
            $model->incrementing = false;
            if (!$model->$keyName) {
                $model->$keyName = Uuid::uuid4()->toString();
            }
        });
        static::saving(function ($model) {
            $keyName = $model->getKeyName();
            $originalId = $model->getOriginal($keyName);
            if ($originalId && $originalId !== $model->$keyName) {
                $model->$keyName = $originalId;
            }
        });
    }
}
