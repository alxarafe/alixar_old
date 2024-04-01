<?php

/* Copyright (C) 2003-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2012       Cedric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2017-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2023       Nick Fragoulis
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

use DoliModules\Billing\Model\CommonInvoiceLine;

/**
 *  Class to manage invoice lines of templates.
 *  Saved into database table llx_facturedet_rec
 */
class FactureLigneRec extends CommonInvoiceLine
{
    /**
     * @var string ID to identify managed object
     */
    public $element = 'facturedetrec';

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'facturedet_rec';

    //! From llx_facturedet_rec
    //! Id facture
    public $fk_facture;
    //! Id parent line
    public $fk_parent_line;

    public $fk_product_fournisseur_price;
    public $fk_fournprice; // For backward compatibility

    public $rang;
    //public $situation_percent;    // Not supported on recurring invoice line

    public $desc;
    public $description;

    public $fk_product_type; // Use instead product_type

    public $fk_contract_line;


    /**
     *  Delete line in database
     *
     *  @param      User    $user       Object user
     *  @param      int     $notrigger  Disable triggers
     *  @return     int                 Return integer <0 if KO, >0 if OK
     */
    public function delete(User $user, $notrigger = 0)
    {
        $error = 0;

        $this->db->begin();

        if (!$error) {
            if (!$notrigger) {
                // Call triggers
                $result = $this->call_trigger('LINEBILLREC_DELETE', $user);
                if ($result < 0) {
                    $error++;
                } // Do also here what you must do to rollback action if trigger fail
                // End call triggers
            }
        }

        if (!$error) {
            $result = $this->deleteExtraFields();
            if ($result < 0) {
                $error++;
            }
        }

        if (!$error) {
            $sql = 'DELETE FROM ' . MAIN_DB_PREFIX . $this->table_element . ' WHERE rowid=' . ((int) $this->id);

            $res = $this->db->query($sql);
            if (!$res) {
                $error++;
                $this->errors[] = $this->db->lasterror();
            }
        }

        // Commit or rollback
        if ($error) {
            $this->db->rollback();
            return -1;
        } else {
            $this->db->commit();
            return 1;
        }
    }


    /**
     *  Get line of template invoice
     *
     *  @param      int     $rowid      Id of invoice
     *  @return     int                 1 if OK, < 0 if KO
     */
    public function fetch($rowid)
    {
        $sql = 'SELECT l.rowid, l.fk_facture, l.fk_parent_line, l.fk_product, l.product_type, l.label as custom_label, l.description, l.product_type, l.price, l.qty, l.vat_src_code, l.tva_tx,';
        $sql .= ' l.localtax1_tx, l.localtax2_tx, l.localtax1_type, l.localtax2_type, l.remise, l.remise_percent, l.subprice,';
        $sql .= ' l.date_start_fill, l.date_end_fill, l.info_bits, l.total_ht, l.total_tva, l.total_ttc,';
        $sql .= ' l.rang, l.special_code,';
        $sql .= ' l.fk_unit, l.fk_contract_line,';
        $sql .= ' l.import_key, l.fk_multicurrency,';
        $sql .= ' l.multicurrency_code, l.multicurrency_subprice, l.multicurrency_total_ht, l.multicurrency_total_tva, l.multicurrency_total_ttc,';
        $sql .= ' l.buy_price_ht, l.fk_product_fournisseur_price,';
        $sql .= ' l.fk_user_author, l.fk_user_modif,';
        $sql .= ' p.ref as product_ref, p.fk_product_type as fk_product_type, p.label as product_label, p.description as product_desc';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'facturedet_rec as l';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product as p ON l.fk_product = p.rowid';
        $sql .= ' WHERE l.rowid = ' . ((int) $rowid);
        $sql .= ' ORDER BY l.rang';

        dol_syslog('FactureRec::fetch', LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result) {
            $objp = $this->db->fetch_object($result);

            $this->id               = $objp->rowid;
            $this->fk_facture       = $objp->fk_facture;
            $this->fk_parent_line   = $objp->fk_parent_line;
            $this->label            = $objp->custom_label; // Label line
            $this->desc             = $objp->description; // Description line
            $this->description      = $objp->description; // Description line
            $this->product_type     = $objp->product_type; // Type of line
            $this->ref              = $objp->product_ref; // Ref product
            $this->product_ref      = $objp->product_ref; // Ref product
            $this->libelle          = $objp->product_label; // deprecated
            $this->product_label = $objp->product_label; // Label product
            $this->product_desc     = $objp->product_desc; // Description product
            $this->fk_product_type  = $objp->fk_product_type; // Type of product
            $this->qty              = $objp->qty;
            $this->price = $objp->price;
            $this->subprice         = $objp->subprice;
            $this->vat_src_code     = $objp->vat_src_code;
            $this->tva_tx           = $objp->tva_tx;
            $this->localtax1_tx     = $objp->localtax1_tx;
            $this->localtax2_tx     = $objp->localtax2_tx;
            $this->localtax1_type   = $objp->localtax1_type;
            $this->localtax2_type   = $objp->localtax2_type;
            $this->remise_percent   = $objp->remise_percent;
            //$this->fk_remise_except = $objp->fk_remise_except;
            $this->fk_product       = $objp->fk_product;
            $this->date_start_fill  = $objp->date_start_fill;
            $this->date_end_fill    = $objp->date_end_fill;
            $this->info_bits        = $objp->info_bits;
            $this->total_ht         = $objp->total_ht;
            $this->total_tva        = $objp->total_tva;
            $this->total_ttc        = $objp->total_ttc;

            $this->rang = $objp->rang;
            $this->special_code = $objp->special_code;
            $this->fk_unit          = $objp->fk_unit;
            $this->fk_contract_line = $objp->fk_contract_line;
            $this->import_key = $objp->import_key;
            $this->fk_multicurrency = $objp->fk_multicurrency;
            $this->multicurrency_code = $objp->multicurrency_code;
            $this->multicurrency_subprice = $objp->multicurrency_subprice;
            $this->multicurrency_total_ht = $objp->multicurrency_total_ht;
            $this->multicurrency_total_tva = $objp->multicurrency_total_tva;
            $this->multicurrency_total_ttc = $objp->multicurrency_total_ttc;

            $this->buy_price_ht = $objp->buy_price_ht;

            $this->fk_product_fournisseur_price = $objp->fk_product_fournisseur_price;
            $this->fk_user_author = $objp->fk_user_author;
            $this->fk_user_modif = $objp->fk_user_modif;

            $this->db->free($result);
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -3;
        }
    }


    /**
     *  Update a line to invoice_rec.
     *
     *  @param      User    $user                   User
     *  @param      int     $notrigger              No trigger
     *  @return     int                             Return integer <0 if KO, Id of line if OK
     */
    public function update(User $user, $notrigger = 0)
    {
        global $conf;

        $error = 0;

        // Clean parameters
        if (empty($this->fk_parent_line)) {
            $this->fk_parent_line = 0;
        }

        include_once DOL_DOCUMENT_ROOT . '/core/lib/price.lib.php';

        $sql = "UPDATE " . MAIN_DB_PREFIX . "facturedet_rec SET";
        $sql .= " fk_facture = " . ((int) $this->fk_facture);
        $sql .= ", fk_parent_line=" . ($this->fk_parent_line > 0 ? $this->fk_parent_line : "null");
        $sql .= ", label=" . (!empty($this->label) ? "'" . $this->db->escape($this->label) . "'" : "null");
        $sql .= ", description='" . $this->db->escape($this->desc) . "'";
        $sql .= ", price=" . price2num($this->price);
        $sql .= ", qty=" . price2num($this->qty);
        $sql .= ", tva_tx=" . price2num($this->tva_tx);
        $sql .= ", vat_src_code='" . $this->db->escape($this->vat_src_code) . "'";
        $sql .= ", localtax1_tx=" . price2num($this->localtax1_tx);
        $sql .= ", localtax1_type='" . $this->db->escape($this->localtax1_type) . "'";
        $sql .= ", localtax2_tx=" . price2num($this->localtax2_tx);
        $sql .= ", localtax2_type='" . $this->db->escape($this->localtax2_type) . "'";
        $sql .= ", fk_product=" . ($this->fk_product > 0 ? $this->fk_product : "null");
        $sql .= ", product_type=" . ((int) $this->product_type);
        $sql .= ", remise_percent=" . price2num($this->remise_percent);
        $sql .= ", subprice=" . price2num($this->subprice);
        $sql .= ", info_bits=" . price2num($this->info_bits);
        $sql .= ", date_start_fill=" . (int) $this->date_start_fill;
        $sql .= ", date_end_fill=" . (int) $this->date_end_fill;
        if (empty($this->skip_update_total)) {
            $sql .= ", total_ht=" . price2num($this->total_ht);
            $sql .= ", total_tva=" . price2num($this->total_tva);
            $sql .= ", total_localtax1=" . price2num($this->total_localtax1);
            $sql .= ", total_localtax2=" . price2num($this->total_localtax2);
            $sql .= ", total_ttc=" . price2num($this->total_ttc);
        }
        $sql .= ", rang=" . ((int) $this->rang);
        $sql .= ", special_code=" . ((int) $this->special_code);
        $sql .= ", fk_unit=" . ($this->fk_unit ? "'" . $this->db->escape($this->fk_unit) . "'" : "null");
        $sql .= ", fk_contract_line=" . ($this->fk_contract_line ? $this->fk_contract_line : "null");
        $sql .= " WHERE rowid = " . ((int) $this->id);

        $this->db->begin();

        dol_syslog(get_class($this) . "::updateline", LOG_DEBUG);

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
                $result = $this->call_trigger('LINEBILLREC_MODIFY', $user);
                if ($result < 0) {
                    $error++;
                }
                // End call triggers
            }

            if ($error) {
                $this->db->rollback();
                return -2;
            } else {
                $this->db->commit();
                return 1;
            }
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -2;
        }
    }
}
