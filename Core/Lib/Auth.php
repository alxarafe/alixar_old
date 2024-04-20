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

abstract class Auth
{
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
        static::logout();

        // If bad user or password, no login
        if ($username !== 'user' || $password !== 'password') {
            return false;
        }

        // Save the cookie
        $rememberme = filter_input(INPUT_POST, 'rememberme');
        $time = time() + 3600;
        if (isset($rememberme)) {
            $time += 365 * 24 * 60 * 60;
        }
        setcookie('login_alixar', $username, $time);
        return true;
    }

    public static function logout()
    {
        // Erase old cookie.
        setcookie('login_alixar', '', time() - 60);
    }

    public static function isLogged()
    {
        /**
         * TODO: This is a test.
         */
        return $_COOKIE['login_alixar'];
    }
}
