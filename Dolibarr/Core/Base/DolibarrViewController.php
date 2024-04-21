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

use Jenssegers\Blade\Blade;

/**
 * Class ViewController. The views controller adds support for views to the generic controller.
 *
 * @package Alxarafe\Base
 */
abstract class DolibarrViewController extends DolibarrGenericController
{
    /**
     * Theme name. TODO: Has to be updated according to the configuration.
     *
     * @var string
     */
    public $theme;

    public function __destruct()
    {
        $this->theme = $_GET['theme'];
        if (empty($this->theme)) {
            $this->theme = 'adminlte';
        }

        if (!isset($this->template)) {
            $this->template = 'index';
        }

        $vars = ['me' => $this];
        $viewPaths = [
            BASE_PATH . '/Templates',
            BASE_PATH . '/Templates/theme/' . $this->theme,
            BASE_PATH . '/Templates/common',
        ];
        $cachePaths = realpath(BASE_PATH . '/../tmp') . '/blade';
        if (!is_dir($cachePaths) && !mkdir($cachePaths) && !is_dir($cachePaths)) {
            die('Could not create cache directory for templates.');
        }
        $blade = new Blade($viewPaths, $cachePaths);
        echo $blade->render($this->template, $vars);
    }
}
