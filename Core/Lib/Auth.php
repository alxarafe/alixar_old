<?php

/* Copyright (C) 2024      Rafael San José      <rsanjose@alxarafe.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Alxarafe\Lib;

use Alxarafe\Model\User;

abstract class Auth
{
    private const COOKIE_NAME = 'alxarafe_login';
    private const COOKIE_USER = self::COOKIE_NAME . '_user';
    private const COOKIE_EXPIRE_TIME = 30 * 86400; // 30 days
    private const COOKIE_SAMESITE = 'Strict';

    public static $user = null;

    public static function isLogged()
    {
        $userId = FILTER_INPUT(INPUT_COOKIE, self::COOKIE_USER);
        $token = FILTER_INPUT(INPUT_COOKIE, self::COOKIE_NAME);
        if (empty($token)) {
            return false;
        }

        if (!isset(self::$user)) {
            self::$user = User::find($userId);
        }

        if (!isset(self::$user)) {
            return false;
        }

        return self::$user->token === $token;
    }

    /**
     * Return true if login is correct with user/mail and password.
     * TODO: This is a test. It will be checked against a user database.
     *
     * @param $email
     * @param $password
     *
     * @return bool
     */
    public static function login($username, $password)
    {
        //static::logout();

        $user = User::where('name', $username)->first();
        if (!isset($user)) {
            return false;
        }

        if (!password_verify($password, $user->password)) {
            return false;
        }

        self::setLoginCookie($user->id);

        return true;
    }

    public static function setLoginCookie($userId)
    {
        $token = self::generateToken();

        if (!isset(self::$user)) {
            self::$user = User::find($userId);
        }
        if (isset(self::$user)) {
            self::$user->saveToken($token);
        }

        $cookie_options = [
            'expires' => time() + self::COOKIE_EXPIRE_TIME,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => true,
            'httponly' => true,
            'samesite' => self::COOKIE_SAMESITE,
        ];

        setcookie(self::COOKIE_USER, $userId);
        setcookie(self::COOKIE_NAME, $token, $cookie_options);
    }

    private static function generateToken($length = 32)
    {
        return bin2hex(random_bytes($length));
    }

    public static function logout()
    {
        // Erase old cookies.
        setcookie(self::COOKIE_USER, '', time() - 60);
        setcookie(self::COOKIE_NAME, '', time() - 60);
    }

}
