<?php

/* Copyright (C) 2023   Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024      Rafael San José      <rsanjose@alxarafe.com>
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

namespace DoliCore\Tools;

/**
 *  \file       htdocs/debugbar/class/DebugBar.php
 *  \brief      Class for debugbar
 *  \ingroup    debugbar
 */

//dol_include_once('/debugbar/class/autoloader.php');

use DebugBar\DebugBar;
use DoliCore\Tools\DataCollector\DolibarrCollector;
use DoliCore\Tools\DataCollector\DolLogsCollector;
use DoliCore\Tools\DataCollector\DolMemoryCollector;
use DoliCore\Tools\DataCollector\DolPhpCollector;
use DoliCore\Tools\DataCollector\DolQueryCollector;
use DoliCore\Tools\DataCollector\DolRequestDataCollector;
use DoliCore\Tools\DataCollector\DolTimeDataCollector;

/**
 * DolibarrDebugBar class
 *
 * @see http://phpdebugbar.com/docs/base-collectors.html#base-collectors
 */
class DolibarrDebugBar extends DebugBar
{
    /**
     * Constructor
     *
     */
    public function __construct()
    {
        global $conf;

        //$this->addCollector(new PhpInfoCollector());
        //$this->addCollector(new DolMessagesCollector());
        $this->addCollector(new DolRequestDataCollector());
        //$this->addCollector(new DolConfigCollector());      // Disabled for security purpose
        $this->addCollector(new DolTimeDataCollector());
        $this->addCollector(new DolPhpCollector());
        $this->addCollector(new DolMemoryCollector());
        //$this->addCollector(new DolExceptionsCollector());
        $this->addCollector(new DolQueryCollector());
        $this->addCollector(new DolibarrCollector());
        if (isModEnabled('syslog')) {
            $this->addCollector(new DolLogsCollector());
        }
    }

    /**
     * Returns a JavascriptRenderer for this instance
     *
     * @return \DebugBar\JavascriptRenderer      String content
     */
    public function getRenderer()
    {
        $renderer = parent::getJavascriptRenderer(DOL_URL_ROOT . '/includes/maximebf/debugbar/src/DebugBar/Resources');
        $renderer->disableVendor('jquery');         // We already have jquery loaded globally by the main.inc.php
        $renderer->disableVendor('fontawesome');    // We already have fontawesome loaded globally by the main.inc.php
        $renderer->disableVendor('highlightjs');    // We don't need this
        $renderer->setEnableJqueryNoConflict(false);    // We don't need no conflict

        return $renderer;
    }
}
