<?php

/* Copyright (C) 2003       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2014  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2006-2007  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2007       Franky Van Liedekerke   <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2011-2023  Philippe Grand	        <philippe.grand@atoo-net.com>
 * Copyright (C) 2013       Florian Henry	        <florian.henry@open-concept.pro>
 * Copyright (C) 2014-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2023-2024  Frédéric France         <frederic.france@free.fr>
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
 *  \file       htdocs/delivery/class/delivery.class.php
 *  \ingroup    delivery
 *  \brief      Delivery Order Management Class File
 */

/**
 *  Management class of delivery note lines
 */
class DeliveryLine extends GenericDocumentLine
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var string ID to identify managed object
     */
    public $element = 'deliverydet';

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'deliverydet';

    /**
     * @var string delivery note lines label
     */
    public $label;

    /**
     * @var string product description
     */
    public $description;

    /**
     * @deprecated
     * @see $product_ref
     */
    public $ref;
    /**
     * @deprecated
     * @see product_label;
     */
    public $libelle;

    // From llx_expeditiondet
    public $qty;
    public $qty_asked;
    public $qty_shipped;

    public $fk_product;
    public $product_desc;
    public $product_type;
    public $product_ref;
    public $product_label;

    public $price;

    public $fk_origin_line;
    public $origin_id;

    /**
     * @var int origin line ID
     */
    public $origin_line_id;

    /**
     * @var int origin line ID
     * @deprecated
     * @see $origin_line_id
     */
    public $commande_ligne_id;


    /**
     *  Constructor
     *
     *  @param  DoliDB  $db     Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }
}
