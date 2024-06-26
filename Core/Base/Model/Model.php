<?php

namespace Alxarafe\Base\Model;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as EloquentModel;

abstract class Model extends EloquentModel
{
    public static function exists(): bool
    {
        $table_name = (new static())->table;
        if (empty($table_name)) {
            return false;
        }
        return DB::schema()->hasTable($table_name);
    }
}
