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

namespace DoliCore\Model;

use DoliCore\Base\Model;

class Menu extends Model
{
    /**
     * Indicates whether the automatic record creation and update fields are to be used.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Name of the table associated with the model. By default, it is the plural model name in
     * snakecase format: 'mailing_unsubscribes'.
     *
     * @var string
     */
    protected $table = 'menu';

    /**
     * List of fields that will be autocompleted with 'null' during the registration of a new record.
     *
     * @var string[]
     */
    protected $fillable = ['module', 'leftmenu', 'fk_mainmenu', 'fk_leftmenu', 'target', 'prefix', 'langs', 'level', 'perms', 'enabled'];

    public static function loadTopMenu($entity, $userType = 0)
    {
        $entities = [0, (int) $entity];
        $types = [(int) $userType, 2];

        return static::where('type', 'top')
            ->whereIn('entity', $entities)
            ->whereIn('usertype', $types)
            ->orderBy('type', 'DESC')
            ->orderBy('position')
            ->orderBy('rowid')
            ->get();
    }

    public static function loadSideMenu($entity, $userType = 0)
    {
        $entities = [0, (int) $entity];
        $types = [(int) $userType, 2];

        return static::where('type', 'left')
            ->whereIn('entity', $entities)
            ->whereIn('usertype', $types)
            ->orderBy('type', 'DESC')
            ->orderBy('position')
            ->orderBy('rowid')
            ->get();
    }
}
