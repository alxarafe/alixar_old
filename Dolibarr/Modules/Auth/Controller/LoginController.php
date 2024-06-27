<?php

/* Copyright (C) 2024       Rafael San José         <rsanjose@alxarafe.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace DoliModules\Auth\Controller;

use Alxarafe\Base\Controller\PublicController;
use DoliCore\Base\Controller\Trait\DolibarrVarsTrait;
use DoliLib\DolibarrAuth;
use DoliModules\User\Controller\DashboardController;

/**
 * This is the Dolibarr Login controller
 */
class LoginController extends PublicController
{
    use DolibarrVarsTrait;

    public $username;
    public $password;
    public $remember;

    public function doIndex(): bool
    {
        return $this->doLogin();
    }

    public function doLogin(): bool
    {
        $this->loadVars();

        $this->template = 'page/admin/login';

        $this->username = filter_input(INPUT_POST, 'username');
        $this->password = filter_input(INPUT_POST, 'password');
        $this->remember = filter_input(INPUT_POST, 'remember') === 'on';

        $login = filter_input(INPUT_POST, 'action') === 'login';
        if (!$login) {
            return true;
        }

        $auth = DolibarrAuth::login($this->username, $this->password);
        if (!$auth) {
            static::addAdvice('Usuario o contraseña incorrectos');
            return true;
        }
        static::addMessage("Usuario '$this->username' identificado correctamente.");

        DolibarrAuth::setSession($this->username);

        $dashboard = new DashboardController();

        dd($this,$dashboard);
        $dashboard->index();
        die();
    }
}
