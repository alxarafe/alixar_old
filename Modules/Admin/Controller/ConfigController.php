<?php

/* Copyright (C) 2024       Rafael San José         <rsanjose@alxarafe.com>
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

namespace Modules\Admin\Controller;

use Alxarafe\Base\Controller\ViewController;

class ConfigController extends ViewController
{

    public function doIndex(): bool
    {
        /**
         * TODO: The value of this variable will be filled in when the roles
         * are correctly implemented.
         */
        $restricted_access = false;

        $this->template = 'page/config';
        if (isset($this->config) && $restricted_access) {
            $this->template = 'page/forbidden';
        }
        return true;
    }

    public function doSave():bool
    {
        dump($_POST);
        return false;
    }
}
