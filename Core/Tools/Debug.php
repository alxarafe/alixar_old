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

namespace Alxarafe\Tools;

use Alxarafe\Tools\DebugBarCollector\PhpCollector;
use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DebugBar;
use DebugBar\DebugBarException;
use DebugBar\JavascriptRenderer;
use DebugBar\StandardDebugBar;

abstract class Debug
{
    /**
     * Private render instance
     *
     * @var JavascriptRenderer
     */
    private static JavascriptRenderer $render;
    /**
     * DebugBar instance
     *
     * @var StandardDebugBar
     */
    private static StandardDebugBar $debugBar;

    /**
     * DebugTool constructor.
     *
     * @throws DebugBarException
     */
    public static function load()
    {
        $shortName = 'Debug';

        self::$debugBar = new StandardDebugBar();
        self::startTimer($shortName, $shortName . ' DebugTool Constructor');

        self::addCollector(new PhpCollector());
        // self::addCollector(new MessagesCollector('Deprecated'));
        // self::addCollector(new MonologCollector(Logger::getLogger()));
        // self::addCollector(new TranslatorCollector());


        $baseUrl = constant('BASE_URL') . '/Templates/DebugBar/Resources';
        self::$render = self::getDebugBar()->getJavascriptRenderer($baseUrl, constant('BASE_PATH'));

        self::stopTimer($shortName);
    }

    /**
     * Initialize the timer
     *
     * @param string $name
     * @param string $message
     */
    public static function startTimer(string $name, string $message): void
    {
        if (!isset(self::$debugBar)) {
            self::$debugBar = new StandardDebugBar();
        }
        self::$debugBar['time']->startMeasure($name, $message);
    }

    public static function addCollector(DataCollectorInterface $collector): DebugBar
    {
        return self::$debugBar->addCollector($collector);
    }

    /**
     * Return the internal debug instance for get the html code.
     *
     * @return StandardDebugBar
     * @throws DebugBarException
     */
    public static function getDebugBar(): ?StandardDebugBar
    {
        if (!isset(self::$debugBar)) {
            return null;
        }
        return self::$debugBar;
    }

    /**
     * Stop the timer
     *
     * @param string $name
     */
    public static function stopTimer(string $name): void
    {
        self::$debugBar['time']->stopMeasure($name);
    }

    /**
     * Gets the necessary calls to include the debug bar in the page header
     *
     * @return string
     */
    public static function getRenderHeader(): string
    {
        return self::$render->renderHead();
        if (constant('DEBUG') !== true) {
            return '<!-- Debug is disabled -->';
        }
        return self::$render->renderHead();
    }

    /**
     * Gets the necessary calls to include the debug bar in the page footer
     *
     * @return string
     */
    public static function getRenderFooter(): string
    {
        return self::$render->render();
        if (constant('DEBUG') !== true) {
            return '<!-- Debug is disabled -->';
        }
        return self::$render->render();
    }

    /**
     * Add an exception to the exceptions tab of the debug bar.
     *
     * TODO: addException is deprecated!
     *
     * @param $exception
     */
    public static function addException($exception): void
    {
        if (constant('DEBUG') !== true) {
            return;
        }
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[0];
        $caller['file'] = substr($caller['file'], strlen(BASE_PATH) - 7);
        self::$debugBar['exceptions']->addException($exception); // Use addThrowable instead!
        // Logger::info('Exception: ' . $exception->getMessage());
    }

    public static function message(string $message): void
    {
        self::addMessage('messages', $message);
    }

    /**
     * Write a message in a channel (tab) of the debug bar.
     *
     * @param string $channel
     * @param string $message
     */
    private static function addMessage(string $channel, string $message): void
    {
        if (!isset(self::$debugBar[$channel])) {
            return;
        }

//        if (!defined('DEBUG') || constant('DEBUG') !== true) {
//            return;
//        }
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
        $caller['file'] = substr($caller['file'], strlen(BASE_PATH) - 7);
        self::$debugBar[$channel]->addMessage($caller['file'] . ' (' . $caller['line'] . '): ' . $message);
    }

    public static function sqlMessage(string $message): void
    {
        self::addMessage('SQL', $message);
    }
}
