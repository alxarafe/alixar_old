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
    /**
     * Theme name. TODO: Has to be updated according to the configuration.
     *
     * @var string
     */
    public $theme = 'eldy';

    public function view($vars = [])
    {
        $viewPaths = [
            BASE_PATH . '/Templates',
            BASE_PATH . '/Templates/theme/' . $this->theme,
        ];
        $cachePaths = realpath(BASE_PATH . '/../tmp') . '/blade';
        if (!is_dir($cachePaths) && !mkdir($cachePaths) && !is_dir($cachePaths)) {
            die('Could not create cache directory for templates.');
        }
        $blade = new Blade($viewPaths, $cachePaths);
        echo $blade->render($this->template, $vars);
    }

    public function __destruct()
    {
        $this->view(['self' => $this]);
        die();
    }
}
