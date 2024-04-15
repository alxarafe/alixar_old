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

namespace Alxarafe\Base;

use Jenssegers\Blade\Blade;

/**
 * Class ViewController. The views controller adds support for views to the generic controller.
 *
 * @package Alxarafe\Base
 */
abstract class ViewController extends GenericController
{
    public $template;

    public function index()
    {
        if (isset($this->template)) {
            $this->view(['self' => $this]);
        }
    }

    public function view($vars = [])
    {
        $viewPaths = BASE_PATH . '/Templates';
        $cachePaths = realpath(BASE_PATH . '/../tmp');
        $blade = new Blade($viewPaths, $cachePaths);
        echo $blade->render($this->template, $vars);
    }
}
