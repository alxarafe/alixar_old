<?php

/* Copyright (C) 2002-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004       Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2011  Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005       Marc Barilley			<marc@ocebo.com>
 * Copyright (C) 2005-2013  Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2006       Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2008       Raphael Bertrand		<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2020  Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2010-2018  Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2014  Christophe Battarel  	<christophe.battarel@altairis.fr>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2014       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2016       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018       Nicolas ZABOURI			<info@inovea-conseil.com>
 * Copyright (C) 2019-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2020		Tobias Sekan			<tobias.sekan@startmail.com>
 * Copyright (C) 2022       Gauthier VERDOL     	<gauthier.verdol@atm-consulting.fr>
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

namespace DoliModules\SupplierProposal\Model;

/**
 *  \file       htdocs/supplier_proposal/class/supplier_proposal.class.php
 *  \brief      File of class to manage supplier proposals
 */

use DoliCore\Base\GenericDocumentLine;

require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/margin/lib/margins.lib.php';
require_once DOL_DOCUMENT_ROOT . '/multicurrency/class/multicurrency.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/commonincoterm.class.php';

/**
 *  Class to manage supplier_proposal lines
 */
class SupplierProposalLine extends GenericDocumentLine
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var string Error code (or message)
     */
    public $error = '';

    /**
     * @var string ID to identify managed object
     */
    public $element = 'supplier_proposaldet';

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'supplier_proposaldet';

    public $oldline;

    /**
     * @var int ID
     */
    public $id;

    /**
     * @var int ID
     */
    public $fk_supplier_proposal;

    /**
     * @var int ID
     */
    public $fk_parent_line;

    public $desc; // Description ligne

    /**
     * @var int ID
     */
    public $fk_product; // Id produit predefini

    /**
     * @deprecated
     * @see $product_type
     */
    public $fk_product_type;
    /**
     * Product type
     * @var int
     * @see Product::TYPE_PRODUCT, Product::TYPE_SERVICE
     */
    public $product_type = Product::TYPE_PRODUCT;

    public $qty;
    public $tva_tx;
    public $vat_src_code;

    public $subprice;
    public $remise_percent;

    /**
     * @var int ID
     */
    public $fk_remise_except;

    public $rang = 0;

    /**
     * @var int ID
     */
    public $fk_fournprice;

    public $pa_ht;
    public $marge_tx;
    public $marque_tx;

    public $special_code; // Tag for special lines (exclusive tags)
    // 1: frais de port
    // 2: ecotaxe
    // 3: option line (when qty = 0)

    public $info_bits = 0; // Liste d'options cumulables:
    // Bit 0:   0 si TVA normal - 1 if TVA NPR
    // Bit 1:   0 ligne normal - 1 if fixed reduction

    public $total_ht; // Total HT de la ligne toute quantite et incluant la remise ligne
    public $total_tva; // Total TVA de la ligne toute quantite et incluant la remise ligne
    public $total_ttc; // Total TTC de la ligne toute quantite et incluant la remise ligne

    public $date_start;
    public $date_end;

    // From llx_product
    /**
     * @deprecated
     * @see $product_ref
     */
    public $ref;

    /**
     * Product reference
     * @var string
     */
    public $product_ref;

    /**
     * @deprecated
     * @see $product_label
     */
    public $libelle;

    /**
     *  Product label
     * @var string
     */
    public $product_label;

    /**
     * Custom label
     * @var string
     */
    public $label;

    /**
     * Product description
     * @var string
     */
    public $product_desc;

    public $localtax1_tx; // Local tax 1
    public $localtax2_tx; // Local tax 2
    public $localtax1_type; // Local tax 1 type
    public $localtax2_type; // Local tax 2 type
    public $total_localtax1; // Line total local tax 1
    public $total_localtax2; // Line total local tax 2

    public $skip_update_total; // Skip update price total for special lines

    public $ref_fourn;
    public $ref_supplier;

    // Multicurrency
    /**
     * @var int ID
     */
    public $fk_multicurrency;

    public $multicurrency_code;
    public $multicurrency_subprice;
    public $multicurrency_total_ht;
    public $multicurrency_total_tva;
    public $multicurrency_total_ttc;

    /**
     *  Class line Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *  Retrieve the propal line object
     *
     * @param int $rowid Propal line id
     *
     * @return int                 Return integer <0 if KO, >0 if OK
     */
    public function fetch($rowid)
    {
        $sql = 'SELECT pd.rowid, pd.fk_supplier_proposal, pd.fk_parent_line, pd.fk_product, pd.label as custom_label, pd.description, pd.price, pd.qty, pd.tva_tx,';
        $sql .= ' pd.date_start, pd.date_end,';
        $sql .= ' pd.remise, pd.remise_percent, pd.fk_remise_except, pd.subprice,';
        $sql .= ' pd.info_bits, pd.total_ht, pd.total_tva, pd.total_ttc, pd.fk_product_fournisseur_price as fk_fournprice, pd.buy_price_ht as pa_ht, pd.special_code, pd.rang,';
        $sql .= ' pd.localtax1_tx, pd.localtax2_tx, pd.total_localtax1, pd.total_localtax2,';
        $sql .= ' p.ref as product_ref, p.label as product_label, p.description as product_desc,';
        $sql .= ' pd.product_type, pd.ref_fourn as ref_produit_fourn,';
        $sql .= ' pd.fk_multicurrency, pd.multicurrency_code, pd.multicurrency_subprice, pd.multicurrency_total_ht, pd.multicurrency_total_tva, pd.multicurrency_total_ttc, pd.fk_unit';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'supplier_proposaldet as pd';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product as p ON pd.fk_product = p.rowid';
        $sql .= ' WHERE pd.rowid = ' . ((int) $rowid);

        $result = $this->db->query($sql);
        if ($result) {
            $objp = $this->db->fetch_object($result);

            $this->id = $objp->rowid;
            $this->fk_supplier_proposal = $objp->fk_supplier_proposal;
            $this->fk_parent_line = $objp->fk_parent_line;
            $this->label = $objp->custom_label;
            $this->desc = $objp->description;
            $this->qty = $objp->qty;
            $this->subprice = $objp->subprice;
            $this->tva_tx = $objp->tva_tx;
            $this->remise_percent = $objp->remise_percent;
            $this->fk_remise_except = $objp->fk_remise_except;
            $this->fk_product = $objp->fk_product;
            $this->info_bits = $objp->info_bits;
            $this->date_start = $this->db->jdate($objp->date_start);
            $this->date_end = $this->db->jdate($objp->date_end);

            $this->total_ht = $objp->total_ht;
            $this->total_tva = $objp->total_tva;
            $this->total_ttc = $objp->total_ttc;

            $this->fk_fournprice = $objp->fk_fournprice;

            $marginInfos = getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $this->fk_fournprice, $objp->pa_ht);
            $this->pa_ht = $marginInfos[0];
            $this->marge_tx = $marginInfos[1];
            $this->marque_tx = $marginInfos[2];

            $this->special_code = $objp->special_code;
            $this->product_type = $objp->product_type;
            $this->rang = $objp->rang;

            $this->ref = $objp->product_ref; // deprecated
            $this->product_ref = $objp->product_ref;
            $this->libelle = $objp->product_label; // deprecated
            $this->product_label = $objp->product_label;
            $this->product_desc = $objp->product_desc;

            $this->ref_fourn = $objp->ref_produit_fourn;

            // Multicurrency
            $this->fk_multicurrency = $objp->fk_multicurrency;
            $this->multicurrency_code = $objp->multicurrency_code;
            $this->multicurrency_subprice = $objp->multicurrency_subprice;
            $this->multicurrency_total_ht = $objp->multicurrency_total_ht;
            $this->multicurrency_total_tva = $objp->multicurrency_total_tva;
            $this->multicurrency_total_ttc = $objp->multicurrency_total_ttc;
            $this->fk_unit = $objp->fk_unit;

            $this->db->free($result);
            return 1;
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *  Insert object line propal in database
     *
     * @param int $notrigger 1=Does not execute triggers, 0= execute triggers
     *
     * @return     int                     Return integer <0 if KO, >0 if OK
     */
    public function insert($notrigger = 0)
    {
        global $conf, $langs, $user;

        $error = 0;

        dol_syslog(get_class($this) . "::insert rang=" . $this->rang);

        // Clean parameters
        if (empty($this->tva_tx)) {
            $this->tva_tx = 0;
        }
        if (empty($this->vat_src_code)) {
            $this->vat_src_code = '';
        }
        if (empty($this->localtax1_tx)) {
            $this->localtax1_tx = 0;
        }
        if (empty($this->localtax2_tx)) {
            $this->localtax2_tx = 0;
        }
        if (empty($this->localtax1_type)) {
            $this->localtax1_type = 0;
        }
        if (empty($this->localtax2_type)) {
            $this->localtax2_type = 0;
        }
        if (empty($this->total_localtax1)) {
            $this->total_localtax1 = 0;
        }
        if (empty($this->total_localtax2)) {
            $this->total_localtax2 = 0;
        }
        if (empty($this->rang)) {
            $this->rang = 0;
        }
        if (empty($this->remise_percent)) {
            $this->remise_percent = 0;
        }
        if (empty($this->info_bits)) {
            $this->info_bits = 0;
        }
        if (empty($this->special_code)) {
            $this->special_code = 0;
        }
        if (empty($this->fk_parent_line)) {
            $this->fk_parent_line = 0;
        }
        if (empty($this->fk_fournprice)) {
            $this->fk_fournprice = 0;
        }
        if (empty($this->fk_unit)) {
            $this->fk_unit = 0;
        }
        if (empty($this->subprice)) {
            $this->subprice = 0;
        }

        if (empty($this->pa_ht)) {
            $this->pa_ht = 0;
        }

        // if buy price not defined, define buyprice as configured in margin admin
        if ($this->pa_ht == 0) {
            $result = $this->defineBuyPrice($this->subprice, $this->remise_percent, $this->fk_product);
            if ($result < 0) {
                return $result;
            } else {
                $this->pa_ht = $result;
            }
        }

        // Check parameters
        if ($this->product_type < 0) {
            return -1;
        }

        $this->db->begin();

        // Insert line into database
        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'supplier_proposaldet';
        $sql .= ' (fk_supplier_proposal, fk_parent_line, label, description, fk_product, product_type,';
        $sql .= ' date_start, date_end,';
        $sql .= ' fk_remise_except, qty, tva_tx, vat_src_code, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type,';
        $sql .= ' subprice, remise_percent, ';
        $sql .= ' info_bits, ';
        $sql .= ' total_ht, total_tva, total_localtax1, total_localtax2, total_ttc, fk_product_fournisseur_price, buy_price_ht, special_code, rang,';
        $sql .= ' ref_fourn,';
        $sql .= ' fk_multicurrency, multicurrency_code, multicurrency_subprice, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc, fk_unit)';
        $sql .= " VALUES (" . $this->fk_supplier_proposal . ",";
        $sql .= " " . ($this->fk_parent_line > 0 ? ((int) $this->fk_parent_line) : "null") . ",";
        $sql .= " " . (!empty($this->label) ? "'" . $this->db->escape($this->label) . "'" : "null") . ",";
        $sql .= " '" . $this->db->escape($this->desc) . "',";
        $sql .= " " . ($this->fk_product ? ((int) $this->fk_product) : "null") . ",";
        $sql .= " '" . $this->db->escape($this->product_type) . "',";
        $sql .= " " . ($this->date_start ? "'" . $this->db->idate($this->date_start) . "'" : "null") . ",";
        $sql .= " " . ($this->date_end ? "'" . $this->db->idate($this->date_end) . "'" : "null") . ",";
        $sql .= " " . ($this->fk_remise_except ? ((int) $this->fk_remise_except) : "null") . ",";
        $sql .= " " . price2num($this->qty, 'MS') . ",";
        $sql .= " " . price2num($this->tva_tx) . ",";
        $sql .= " '" . $this->db->escape($this->vat_src_code) . "',";
        $sql .= " " . price2num($this->localtax1_tx) . ",";
        $sql .= " " . price2num($this->localtax2_tx) . ",";
        $sql .= " '" . $this->db->escape($this->localtax1_type) . "',";
        $sql .= " '" . $this->db->escape($this->localtax2_type) . "',";
        $sql .= " " . price2num($this->subprice, 'MU') . ",";
        $sql .= " " . ((float) $this->remise_percent) . ",";
        $sql .= " " . (isset($this->info_bits) ? ((int) $this->info_bits) : "null") . ",";
        $sql .= " " . price2num($this->total_ht, 'MT') . ",";
        $sql .= " " . price2num($this->total_tva, 'MT') . ",";
        $sql .= " " . price2num($this->total_localtax1, 'MT') . ",";
        $sql .= " " . price2num($this->total_localtax2, 'MT') . ",";
        $sql .= " " . price2num($this->total_ttc, 'MT') . ",";
        $sql .= " " . (!empty($this->fk_fournprice) ? ((int) $this->fk_fournprice) : "null") . ",";
        $sql .= " " . (isset($this->pa_ht) ? price2num($this->pa_ht, 'MU') : "null") . ",";
        $sql .= ' ' . ((int) $this->special_code) . ',';
        $sql .= ' ' . ((int) $this->rang) . ',';
        $sql .= " '" . $this->db->escape($this->ref_fourn) . "'";
        $sql .= ", " . ($this->fk_multicurrency > 0 ? ((int) $this->fk_multicurrency) : 'null');
        $sql .= ", '" . $this->db->escape($this->multicurrency_code) . "'";
        $sql .= ", " . price2num($this->multicurrency_subprice, 'CU');
        $sql .= ", " . price2num($this->multicurrency_total_ht, 'CT');
        $sql .= ", " . price2num($this->multicurrency_total_tva, 'CT');
        $sql .= ", " . price2num($this->multicurrency_total_ttc, 'CT');
        $sql .= ", " . ($this->fk_unit ? ((int) $this->fk_unit) : 'null');
        $sql .= ')';

        dol_syslog(get_class($this) . '::insert', LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . 'supplier_proposaldet');

            if (!$error) {
                $result = $this->insertExtraFields();
                if ($result < 0) {
                    $error++;
                }
            }

            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('LINESUPPLIER_PROPOSAL_INSERT', $user);
                if ($result < 0) {
                    $this->db->rollback();
                    return -1;
                }
                // End call triggers
            }

            $this->db->commit();
            return 1;
        } else {
            $this->error = $this->db->error() . " sql=" . $sql;
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Delete line in database
     *
     * @param User $user User making the deletion
     *
     * @return  int                 Return integer <0 if KO, >0 if OK
     */
    public function delete($user)
    {
        $error = 0;

        $this->db->begin();

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "supplier_proposaldet";
        $sql .= " WHERE rowid = " . ((int) $this->id);

        if ($this->db->query($sql)) {
            // Remove extrafields
            if (!$error) {
                $result = $this->deleteExtraFields();
                if ($result < 0) {
                    $error++;
                    dol_syslog(get_class($this) . "::delete error -4 " . $this->error, LOG_ERR);
                }
            }

            // Call trigger
            $result = $this->call_trigger('LINESUPPLIER_PROPOSAL_DELETE', $user);
            if ($result < 0) {
                $this->db->rollback();
                return -1;
            }
            // End call triggers

            $this->db->commit();

            return 1;
        } else {
            $this->error = $this->db->error() . " sql=" . $sql;
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *  Update propal line object into DB
     *
     * @param int $notrigger 1=Does not execute triggers, 0= execute triggers
     *
     * @return int                 Return integer <0 if ko, >0 if ok
     */
    public function update($notrigger = 0)
    {
        global $conf, $langs, $user;

        $error = 0;

        // Clean parameters
        if (empty($this->tva_tx)) {
            $this->tva_tx = 0;
        }
        if (empty($this->localtax1_tx)) {
            $this->localtax1_tx = 0;
        }
        if (empty($this->localtax2_tx)) {
            $this->localtax2_tx = 0;
        }
        if (empty($this->total_localtax1)) {
            $this->total_localtax1 = 0;
        }
        if (empty($this->total_localtax2)) {
            $this->total_localtax2 = 0;
        }
        if (empty($this->localtax1_type)) {
            $this->localtax1_type = 0;
        }
        if (empty($this->localtax2_type)) {
            $this->localtax2_type = 0;
        }
        if (empty($this->marque_tx)) {
            $this->marque_tx = 0;
        }
        if (empty($this->marge_tx)) {
            $this->marge_tx = 0;
        }
        if (empty($this->remise_percent)) {
            $this->remise_percent = 0;
        }
        if (empty($this->info_bits)) {
            $this->info_bits = 0;
        }
        if (empty($this->special_code)) {
            $this->special_code = 0;
        }
        if (empty($this->fk_parent_line)) {
            $this->fk_parent_line = 0;
        }
        if (empty($this->fk_fournprice)) {
            $this->fk_fournprice = 0;
        }
        if (empty($this->fk_unit)) {
            $this->fk_unit = 0;
        }
        if (empty($this->subprice)) {
            $this->subprice = 0;
        }

        if (empty($this->pa_ht)) {
            $this->pa_ht = 0;
        }

        // if buy price not defined, define buyprice as configured in margin admin
        if ($this->pa_ht == 0) {
            $result = $this->defineBuyPrice($this->subprice, $this->remise_percent, $this->fk_product);
            if ($result < 0) {
                return $result;
            } else {
                $this->pa_ht = $result;
            }
        }

        $this->db->begin();

        // Mise a jour ligne en base
        $sql = "UPDATE " . MAIN_DB_PREFIX . "supplier_proposaldet SET";
        $sql .= " description='" . $this->db->escape($this->desc) . "'";
        $sql .= " , label=" . (!empty($this->label) ? "'" . $this->db->escape($this->label) . "'" : "null");
        $sql .= " , product_type=" . ((int) $this->product_type);
        $sql .= " , date_start=" . ($this->date_start ? "'" . $this->db->idate($this->date_start) . "'" : "null");
        $sql .= " , date_end=" . ($this->date_end ? "'" . $this->db->idate($this->date_end) . "'" : "null");
        $sql .= " , tva_tx='" . price2num($this->tva_tx) . "'";
        $sql .= " , localtax1_tx=" . price2num($this->localtax1_tx);
        $sql .= " , localtax2_tx=" . price2num($this->localtax2_tx);
        $sql .= " , localtax1_type='" . $this->db->escape($this->localtax1_type) . "'";
        $sql .= " , localtax2_type='" . $this->db->escape($this->localtax2_type) . "'";
        $sql .= " , qty='" . price2num($this->qty) . "'";
        $sql .= " , subprice=" . price2num($this->subprice);
        $sql .= " , remise_percent=" . price2num($this->remise_percent);
        $sql .= " , info_bits='" . $this->db->escape($this->info_bits) . "'";
        if (empty($this->skip_update_total)) {
            $sql .= " , total_ht=" . price2num($this->total_ht);
            $sql .= " , total_tva=" . price2num($this->total_tva);
            $sql .= " , total_ttc=" . price2num($this->total_ttc);
            $sql .= " , total_localtax1=" . price2num($this->total_localtax1);
            $sql .= " , total_localtax2=" . price2num($this->total_localtax2);
        }
        $sql .= " , fk_product_fournisseur_price=" . (!empty($this->fk_fournprice) ? "'" . $this->db->escape($this->fk_fournprice) . "'" : "null");
        $sql .= " , buy_price_ht=" . price2num($this->pa_ht);
        if (strlen($this->special_code)) {
            $sql .= " , special_code=" . ((int) $this->special_code);
        }
        $sql .= " , fk_parent_line=" . ($this->fk_parent_line > 0 ? $this->fk_parent_line : "null");
        if (!empty($this->rang)) {
            $sql .= ", rang=" . ((int) $this->rang);
        }
        $sql .= " , ref_fourn=" . (!empty($this->ref_fourn) ? "'" . $this->db->escape($this->ref_fourn) . "'" : "null");
        $sql .= " , fk_unit=" . ($this->fk_unit ? $this->fk_unit : 'null');

        // Multicurrency
        $sql .= " , multicurrency_subprice=" . price2num($this->multicurrency_subprice);
        $sql .= " , multicurrency_total_ht=" . price2num($this->multicurrency_total_ht);
        $sql .= " , multicurrency_total_tva=" . price2num($this->multicurrency_total_tva);
        $sql .= " , multicurrency_total_ttc=" . price2num($this->multicurrency_total_ttc);

        $sql .= " WHERE rowid = " . ((int) $this->id);

        dol_syslog(get_class($this) . "::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if (!$error) {
                $result = $this->insertExtraFields();
                if ($result < 0) {
                    $error++;
                }
            }

            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('LINESUPPLIER_PROPOSAL_MODIFY', $user);
                if ($result < 0) {
                    $this->db->rollback();
                    return -1;
                }
                // End call triggers
            }

            $this->db->commit();
            return 1;
        } else {
            $this->error = $this->db->error();
            $this->db->rollback();
            return -2;
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

    /**
     *  Update DB line fields total_xxx
     *  Used by migration
     *
     * @return     int     Return integer <0 if ko, >0 if ok
     */
    public function update_total()
    {
        // phpcs:enable
        $this->db->begin();

        // Mise a jour ligne en base
        $sql = "UPDATE " . MAIN_DB_PREFIX . "supplier_proposaldet SET";
        $sql .= " total_ht=" . price2num($this->total_ht, 'MT');
        $sql .= ",total_tva=" . price2num($this->total_tva, 'MT');
        $sql .= ",total_ttc=" . price2num($this->total_ttc, 'MT');
        $sql .= " WHERE rowid = " . ((int) $this->id);

        dol_syslog("SupplierProposalLine::update_total", LOG_DEBUG);

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->db->commit();
            return 1;
        } else {
            $this->error = $this->db->error();
            $this->db->rollback();
            return -2;
        }
    }
}
