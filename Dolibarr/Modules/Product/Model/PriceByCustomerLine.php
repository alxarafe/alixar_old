<?php

/* Copyright (C) 2007-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Florian Henry           <florian.henry@open-concept.pro>
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

namespace DoliModules\Product\Model;

/**
 * \file htdocs/product/class/productcustomerprice.class.php
 * \ingroup produit
 * \brief File of class to manage predefined price products or services by customer
 */

namespace Alixar\product\class;

/**
 * File of class to manage predefined price products or services by customer lines
 */
class PriceByCustomerLine
{
    /**
     * @var int ID
     */
    public $id;

    /**
     * @var int Entity
     */
    public $entity;

    public $datec = '';
    public $tms = '';

    /**
     * @var int ID
     */
    public $fk_product;

    /**
     * @var string Customer reference
     */
    public $ref_customer;

    /**
     * @var int Thirdparty ID
     */
    public $fk_soc;

    public $price;
    public $price_ttc;
    public $price_min;
    public $price_min_ttc;
    public $price_base_type;
    public $default_vat_code;
    public $tva_tx;
    public $recuperableonly;
    public $localtax1_tx;
    public $localtax2_tx;

    /**
     * @var int User ID
     */
    public $fk_user;
    public $price_label;

    public $import_key;
    public $socname;
    public $prodref;
}
