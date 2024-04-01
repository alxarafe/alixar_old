<?php

/* Copyright (C) 2020       Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Rafael San José             <rsanjose@alxarafe.com>
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

use DoliCore\Base\GenericDocumentLine;

/**
 * \file        class/recruitmentcandidature.class.php
 * \ingroup     recruitment
 * \brief       This file is a CRUD class file for RecruitmentCandidature (Create/Read/Update/Delete)
 */

class RecruitmentCandidatureLine extends GenericDocumentLine
{
    // To complete with content of an object RecruitmentCandidatureLine
    // We should have a field rowid, fk_recruitmentcandidature and position

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
