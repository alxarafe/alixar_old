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

namespace DoliCore\Base;

use Illuminate\Database\Eloquent\Model as EloquentModel;

//use Illuminate\Database\Capsule\Manager as DB;

/**
 * This class implements an Eloquent Model
 *
 * This class is only needed for compatibility with Dolibarr.
 *
 * @package DoliCore\Base
 */
abstract class Model extends EloquentModel
{
    /**
     * Name of the field containing the record creation date, by default 'created_at'.
     */
    const CREATED_AT = 'date_creat';

    /**
     * Name of the field that contains the date of the last update of the record, by default 'updated_at'.
     */
    const UPDATED_AT = 'tms';

    /**
     * Primary key name, default is 'id'.
     *
     * @var string
     */
    protected $primaryKey = 'rowid';
}
