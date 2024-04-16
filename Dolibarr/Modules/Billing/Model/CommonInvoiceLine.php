<?php

/* Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2023		Nick Fragoulis
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

namespace DoliModules\Billing\Model;

/**
 *       \file       htdocs/core/class/commoninvoice.class.php
 *       \ingroup    core
 *       \brief      File of the superclass of invoices classes (customer and supplier)
 */

use DoliCore\Base\GenericDocumentLine;

/**
 *  Parent class of all other business classes for details of elements (invoices, contracts, proposals, orders, ...)
 */
abstract class CommonInvoiceLine extends GenericDocumentLine
{
    /**
     * Custom label of line. Not used by default.
     * @deprecated
     */
    public $label;

    /**
     * @deprecated
     * @see $product_ref
     */
    public $ref; // Product ref (deprecated)
    /**
     * @deprecated
     * @see $product_label
     */
    public $libelle; // Product label (deprecated)

    /**
     * Type of the product. 0 for product 1 for service
     * @var int
     */
    public $product_type = 0;

    /**
     * Product ref
     * @var string
     */
    public $product_ref;

    /**
     * Product label
     * @var string
     */
    public $product_label;

    /**
     * Product description
     * @var string
     */
    public $product_desc;

    /**
     * Quantity
     * @var double
     */
    public $qty;

    /**
     * Unit price before taxes
     * @var float
     */
    public $subprice;

    /**
     * Unit price before taxes
     * @var float
     * @deprecated
     */
    public $price;

    /**
     * Id of corresponding product
     * @var int
     */
    public $fk_product;

    /**
     * VAT code
     * @var string
     */
    public $vat_src_code;

    /**
     * VAT %
     * @var float
     */
    public $tva_tx;

    /**
     * Local tax 1 %
     * @var float
     */
    public $localtax1_tx;

    /**
     * Local tax 2 %
     * @var float
     */
    public $localtax2_tx;

    /**
     * Local tax 1 type
     * @var int<0,6>        From 1 to 6, or 0 if not found
     * @see getLocalTaxesFromRate()
     */
    public $localtax1_type;

    /**
     * Local tax 2 type
     * @var int<0,6>        From 1 to 6, or 0 if not found
     * @see getLocalTaxesFromRate()
     */
    public $localtax2_type;

    /**
     * Percent of discount
     * @var float
     */
    public $remise_percent;

    /**
     * Fixed discount
     * @var float
     * @deprecated
     */
    public $remise;

    /**
     * Total amount before taxes
     * @var float
     */
    public $total_ht;

    /**
     * Total VAT amount
     * @var float
     */
    public $total_tva;

    /**
     * Total local tax 1 amount
     * @var float
     */
    public $total_localtax1;

    /**
     * Total local tax 2 amount
     * @var float
     */
    public $total_localtax2;

    /**
     * Total amount with taxes
     * @var float
     */
    public $total_ttc;

    public $date_start_fill; // If set to 1, when invoice is created from a template invoice, it will also auto set the field date_start at creation
    public $date_end_fill; // If set to 1, when invoice is created from a template invoice, it will also auto set the field date_end at creation

    public $buy_price_ht;
    public $buyprice; // For backward compatibility
    public $pa_ht; // For backward compatibility

    public $marge_tx;
    public $marque_tx;

    /**
     * List of cumulative options:
     * Bit 0:   0 for common VAT - 1 if VAT french NPR
     * Bit 1:   0 si ligne normal - 1 si bit discount (link to line into llx_remise_except)
     * @var int
     */
    public $info_bits = 0;

    public $special_code = 0;

    /**
     * @deprecated  Use user_creation_id
     */
    public $fk_user_author;
    /**
     * @deprecated  Use user_modification_id
     */
    public $fk_user_modif;

    public $fk_accounting_account;
}
