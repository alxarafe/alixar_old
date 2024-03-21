<?php

namespace Alxarafe\Base;

use Illuminate\Database\Capsule\Manager as DB;

class Database
{
    public function __construct()
    {
        $capsule = new DB();
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'alixar',
            'username' => 'root',
            'password' => 'LesLuthiers',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => 'alx_',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}