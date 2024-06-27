<?php

namespace Alxarafe\Model;

use Alxarafe\Base\Model\Model;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

class User extends Model
{
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password', 'token', 'is_admin'];
    protected $hidden = ['password', 'token'];

    public static function createTable()
    {
        DB::schema()->create((new static())->table, function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('token')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });
    }

    public static function createAdmin(): bool
    {
        return (null !== User::create([
                'name' => 'admin',
                'email' => 'info@domain.com',
                'password' => password_hash('password', PASSWORD_ARGON2ID),
                'token' => null,
                'is_admin' => true,
            ])
        );
    }

    public function saveToken($token)
    {
        $this->attributes['token'] = $token;
        return $this->save();
    }

    public function getToken()
    {
        return $this->attributes['token'];
    }
}
