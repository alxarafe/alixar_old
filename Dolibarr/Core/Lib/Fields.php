<?php

/* Copyright (C) 2024       Rafael San José         <rsanjose@alxarafe.com>
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

namespace DoliCore\Lib;

abstract class Fields
{
    public static function getArrayFields($fields, $prefix = 't', $arrayfields = [])
    {
        foreach ($fields as $key => $val) {
            $value = static::getVisibleField($val);
            if ($value === false) {
                continue;
            }
            $arrayfields[$prefix . '.' . $key] = $value;
        }
        return $arrayfields;
    }

    public static function getVisibleField($val)
    {
        // If $val['visible']==0, then we never show the field
        if (!empty($val['visible'])) {
            $visible = (int) dol_eval($val['visible'], 1);
            return [
                'label' => $val['label'],
                'checked' => (($visible < 0) ? 0 : 1),
                'enabled' => (abs($visible) != 3 && (int) dol_eval($val['enabled'], 1)),
                'position' => $val['position'],
                'help' => $val['help'] ?? '',
            ];
        }
        return false;
    }
}
