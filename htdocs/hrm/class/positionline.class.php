<?php

/* Copyright (C) 2017       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2021       Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2021       Greg Rastklan           <greg.rastklan@atm-consulting.fr>
 * Copyright (C) 2021       Jean-Pascal BOUDET      <jean-pascal.boudet@atm-consulting.fr>
 * Copyright (C) 2021       Grégory BLEMAND         <gregory.blemand@atm-consulting.fr>
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
 * \file        class/position.class.php
 * \ingroup     hrm
 * \brief       This file is a CRUD class file for Position (Create/Read/Update/Delete)
 */

use DoliCore\Base\GenericDocumentLine;

/**
 * Class PositionLine. You can also remove this and generate a CRUD class for lines objects.
 */
class PositionLine extends GenericDocumentLine
{
    // To complete with content of an object PositionLine
    // We should have a field rowid , fk_position and position

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
