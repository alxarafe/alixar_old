<?php

/* Copyright (C) 2023   Laurent Destailleur     <eldy@users.sourceforge.net>
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

/**
 *  \file       htdocs/debugbar/class/DataCollector/DolQueryCollector.php
 *  \brief      Class for debugbar collection
 *  \ingroup    debugbar
 */

namespace Alxarafe\Tools\DebugBarCollector;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

/**
 * DolQueryCollector class
 */
class DolQueryCollector extends DataCollector implements Renderable, AssetProvider
{
    /**
     * @var object Database handler
     */
    protected $db;

    /**
     * Constructor
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Return collected data
     *
     * @return array  Array
     */
    public function collect()
    {
        $queries = [];
        $totalExecTime = 0;
        $totalMemoryUsage = 0;
        $totalFailed = 0;
        foreach ($this->db->queries as $query) {
            $queries[] = [
                'sql' => $query['sql'],
                'duration' => $query['duration'],
                'duration_str' => round($query['duration'] * 1000, 2),
                'memory' => $query['memory_usage'],
                'is_success' => $query['is_success'],
                'error_code' => $query['error_code'],
                'error_message' => $query['error_message'],
            ];
            $totalExecTime += $query['duration'];
            $totalMemoryUsage += $query['memory_usage'];
            if (!$query['is_success']) {
                $totalFailed += 1;
            }
        }

        return [
            'nb_statements' => count($queries),
            'nb_failed_statements' => $totalFailed,
            'accumulated_duration' => $totalExecTime,
            'memory_usage' => $totalMemoryUsage,
            'statements' => $queries,
        ];
    }

    /**
     *  Return collector name
     *
     * @return string  Name
     */
    public function getName()
    {
        return 'query';
    }

    /**
     *  Return widget settings
     *
     * @return array      Array
     */
    public function getWidgets()
    {
        global $langs;

        $title = $langs->transnoentities('SQL');

        return [
            "$title" => [
                "icon" => "arrow-right",
                "widget" => "PhpDebugBar.Widgets.SQLQueriesWidget",
                "map" => "query",
                "default" => "[]",
            ],
            "$title:badge" => [
                "map" => "query.nb_statements",
                "default" => 0,
            ],
        ];
    }

    /**
     *  Return assets
     *
     * @return array   Array
     */
    public function getAssets()
    {
        return [
            'css' => 'widgets/sqlqueries/widget.css',
            'js' => 'widgets/sqlqueries/widget.js',
        ];
    }
}
