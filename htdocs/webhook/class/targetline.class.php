<?php

/* Copyright (C) 2017       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Rafael San José         <rsanjose@alxarafe.com>
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
 * \file        class/target.class.php
 * \ingroup     webhook
 * \brief       This file is a CRUD class file for Target (Create/Read/Update/Delete)
 */

use DoliCore\Base\GenericDocumentLine;

/**
 * Class TargetLine. You can also remove this and generate a CRUD class for lines objects.
 */
class TargetLine extends GenericDocumentLine
{
    // To complete with content of an object TargetLine
    // We should have a field rowid, fk_target and position

    /**
     * @var int  Does object support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 0;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }
}
