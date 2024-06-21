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

use Alxarafe\Tools\Debug;
use Illuminate\Support\Str;
use stdClass;

/**
 * Class GenericController. The generic controller contains what is necessary for any controller
 *
 * @package Alxarafe\Base
 */
abstract class GenericController
{
    /**
     * Contains the action to execute.
     *
     * @var string|null
     */
    public ?string $action;

    private $path;

    /**
     * GenericController constructor.
     */
    public function __construct()
    {
        $this->action = filter_input(INPUT_POST, 'action');
        if ($this->action === null) {
            $this->action = filter_input(INPUT_GET, 'action');
        }
        if ($this->action === null) {
            $this->action = 'index';
        }
    }

    /**
     * Returns the generic url of the controller;
     *
     * @param $full
     *
     * @return string
     */
    public static function url($full = true)
    {
        $url = '';
        if ($full) {
            $url .= BASE_URL . '/index.php';
        }

        $url .=
            '?module=' . filter_input(INPUT_GET, 'module') .
            '&controller=' . filter_input(INPUT_GET, 'controller');

        $action = filter_input(INPUT_GET, 'action');
        if ($action) {
            $url .= '&action=' . $action;
        }

        return $url;
    }

    /**
     * Execute the selected action, returning true if successful.
     *
     * @param bool $executeActions
     *
     * @return bool
     */
    public function index(bool $executeActions = true): bool
    {
        if (!$executeActions) {
            return false;
        }
        return $this->executeAction();
    }

    /**
     * Execute the selected action, returning true if successful.
     *
     * @return bool
     */
    private function executeAction(): bool
    {
        $actionMethod = 'do' . ucfirst(Str::camel($this->action ?? 'index'));
        if (!method_exists($this, $actionMethod)) {
            Debug::message('Does not exist the method ' . $actionMethod);
            return false;
        }
        return $this->beforeAction() && $this->$actionMethod() && $this->afterAction();
    }


    /**
     * You can include code here that is common to call all controller actions.
     * If you need to do something, override this method.
     *
     * @return bool
     */
    public function beforeAction(): bool
    {
        return true;
    }


    /**
     * You can include code here common to calling all controller actions, which will be executed after the action.
     * If you need to do something, override this method.
     *
     * @return bool
     */
    public function afterAction(): bool
    {
        return true;
    }
}
