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

namespace Alxarafe\Base\Controller;

// use Illuminate\Database\Capsule\Manager as DB;
use Alxarafe\Base\Controller\Trait\DbTrait;
use Alxarafe\Lib\Auth;

/**
 * Class Controller. Controller is the general purpose controller and requires the user to be authenticated.
 *
 * @package Alxarafe\Base
 */
abstract class Controller extends ViewController
{
    use DbTrait;
    private static $config=null;

    public function __construct()
    {
        static::connectDb();

        // $db = DB::connection()->getPdo();

        parent::__construct();
        $this->checkLogin();
    }

    public function checkLogin()
    {
        if ($this->action === 'logout') {
            return $this->doLogout();
        }
        if ($this->action !== 'login') {
            return true;
        }
        $this->doLogin();
    }

    public function doLogout()
    {
        Auth::logout();
        return true;
    }

    public function doLogin()
    {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $ok = Auth::login($username, $password);
        if ($ok) {
            $this->message = 'Login ok';
        } else {
            $this->alert = 'Login KO';
        }
        return $ok;
    }

    public function index(bool $executeActions = true): bool
    {
        $log = $this->isLogged();
        if (!$log) {
            $login = $this->doLogin();
            if (!$login) {
                $this->template = 'auth/login';
                $this->action = 'index';
                $executeActions = false;
            }
        }
        return parent::index($executeActions);
    }

    private function isLogged()
    {
        if (!isset($this->username)) {
            $this->username = Auth::isLogged();
        }
        return isset($this->username);
    }
}
