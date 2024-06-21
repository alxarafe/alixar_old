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

namespace Alxarafe\Base\Controller\Trait;

use Jenssegers\Blade\Blade;

trait ViewTrait
{
    /**
     * Theme name. TODO: Has to be updated according to the configuration.
     *
     * @var string
     */
    public $theme;

    /**
     * Code lang for <html lang> tag
     *
     * @var string
     */
    public $lang = 'en';

    public $body_class;

    public $templatesPath;
    public $template;
    public $title;

    public function __destruct()
    {
        if (!isset($this->template)) {
            return;
        }

        if (!isset($this->theme)) {
            $this->theme = 'alixar';
        }

        if (!isset($this->title)) {
            $this->title = 'Alixar';
        }

        $vars = ['me' => $this];
        $viewPaths = [
            $this->templatesPath,
            BASE_PATH . '/Templates',
            BASE_PATH . '/Templates/theme/' . $this->theme,
            BASE_PATH . '/Templates/common',
        ];
        $cachePaths = realpath(BASE_PATH . '/..') . '/tmp/blade';
        if (!is_dir($cachePaths) && !mkdir($cachePaths, 0777, true) && !is_dir($cachePaths)) {
            die('Could not create cache directory for templates: ' . $cachePaths);
        }
        $blade = new Blade($viewPaths, $cachePaths);
        echo $blade->render($this->template, $vars);
    }

    public function getTemplatesPath(): string
    {
        return $this->templatesPath;
    }

    public function setTemplatesPath(string $path)
    {
        $this->templatesPath = $path;
    }
}
