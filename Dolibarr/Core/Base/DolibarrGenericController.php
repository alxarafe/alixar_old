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

namespace DoliCore\Base;

use Alxarafe\Base\GenericController;

/**
 * Class DolibarrGenericController. The generic controller contains what is necessary for any controller
 *
 * This class is only needed for compatibility with Dolibarr.
 *
 * @package DoliCore\Base
 */
abstract class DolibarrGenericController extends GenericController
{
    /**
     * Contains the controller to execute.
     *
     * @var string
     */
    protected string $controller;

    /**
     * GenericController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->controller = filter_input(INPUT_GET, GET_FILENAME_VAR);
        $this->action = filter_input(INPUT_GET, 'action');

        if (empty($this->controller)) {
            $this->controller = 'index';
        }

        if (method_exists($this, $this->controller)) {
            return $this->{$this->controller}();
        }

        return $this->index();
    }

    /**
     * Returns the generic url of the controller;
     *
     * @param $full
     *
     * @return string
     */
    public static function url($full = false)
    {
        $url = '';
        if ($full) {
            $url .= BASE_URL . '/index.php';
        }

        $url .=
            '?' . GET_ROUTE_VAR . '=' . filter_input(INPUT_GET, GET_ROUTE_VAR) .
            '&' . GET_FILENAME_VAR . '=' . filter_input(INPUT_GET, GET_FILENAME_VAR);

        return $url;
    }
}
