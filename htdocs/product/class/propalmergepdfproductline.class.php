<?php

/* Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2015 		Florian HENRY 			<florian.henry@open-concept.pro>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *  \file       htdocs/product/class/propalmergepdfproductline.class.php
 *  \ingroup    product
 *  \brief      This file is an CRUD class file (Create/Read/Update/Delete)
 */

namespace Alixar\product\class;

/**
 * Class to manage propal merge of product line
 */
class PropalmergepdfproductLine
{
    /**
     * @var int ID
     */
    public $id;

    /**
     * @var int ID
     */
    public $fk_product;

    /**
     * @var string Filename
     */
    public $file_name;

    /**
     * @var string Code lang
     */
    public $lang;

    /**
     * @var int ID
     */
    public $fk_user_author;

    /**
     * @var int ID
     */
    public $fk_user_mod;

    public $datec = '';
    public $tms = '';
    public $import_key;

    /**
     *  Constructor
     */
    public function __construct()
    {
        return;
    }
}
