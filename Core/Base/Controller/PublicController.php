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

use Alxarafe\Base\Controller\Trait\DbTrait;

/**
 * Class PublicController. The public controller is the controller that has support for views, but does not require the user to be authenticated.
 *
 * @package Alxarafe\Base
 */
abstract class PublicController extends ViewController
{
    use DbTrait;

    function __construct()
    {
        parent::__construct();
        static::connectDb();
    }
}
