<?php

/* Copyright (C) 2024       Rafael San José         <rsanjose@alxarafe.com>
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

namespace DoliCore\Lib;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Class AveryLabels
 *
 * @package DoliCore\Lib
 */
class AveryLabels
{
    /**
     * Contains label information.
     *
     * @var array|null
     */
    private static $averyLabels;

    /**
     * Get the label array statically
     *
     * @return array
     */
    public static function getAveryLables(): array
    {
        if (!isset(static::$averyLabels)) {
            static::$averyLabels = static::loadAveryLabels();
        }

        return static::$averyLabels;
    }

    /**
     * Loads the info into static averyLabels var.
     *
     * @return array
     */
    private static function loadAveryLabels()
    {
        /**
         * Unit of metric are defined into field 'metric' in mm.
         * To get into inch, just /25.4
         * Size of pages available on: http://www.worldlabel.com/Pages/pageaverylabels.htm
         * _PosX = marginLeft+(_COUNTX*(width+SpaceX));
         */

        $sql = "SELECT rowid, code, name, paper_size, orientation, metric, leftmargin, topmargin, nx, ny, spacex, spacey, width, height, font_size, custom_x, custom_y, active FROM " . MAIN_DB_PREFIX . "c_format_cards WHERE active=1 ORDER BY code ASC";
        $labels = DB::select($sql);
        $result = [];
        foreach ($labels as $label) {
            $result[$label->code] = [
                'name' => $label->name . ' (' . $label->paper_size . ' - ' . $label->nx . 'x' . $label->ny . ')',
                'paper-size' => $label->paper_size,
                'orientation' => $label->orientation,
                'metric' => $label->metric,
                'marginLeft' => $label->leftmargin,
                'marginTop' => $label->topmargin,
                'NX' => $label->nx,
                'NY' => $label->ny,
                'SpaceX' => $label->spacex,
                'SpaceY' => $label->spacey,
                'width' => $label->width,
                'height' => $label->height,
                'font-size' => $label->font_size,
                'custom_x' => $label->custom_x,
                'custom_y' => $label->custom_y,
            ];
        }
        return $result;
    }
}