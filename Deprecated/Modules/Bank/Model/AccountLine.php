<?php

/* Copyright (C) 2001-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Christophe Combelles	<ccomb@free.fr>
 * Copyright (C) 2005-2010	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2015-2016	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2015-2017	Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2016		Ferran Marcet   		<fmarcet@2byte.es>
 * Copyright (C) 2019		JC Prieto				<jcprieto@virtual20.com><prietojc@gmail.com>
 * Copyright (C) 2022-2024  Frédéric France         <frederic.france@free.fr>
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

namespace DoliModules\Bank\Model;

/**
 *  \file       htdocs/compta/bank/class/account.class.php
 *  \ingroup    bank
 *  \brief      File of class to manage bank accounts
 */

use DoliCore\Base\GenericDocumentLine;
use DoliDB;

/**
 *  Class to manage bank transaction lines
 */
class AccountLine extends GenericDocumentLine
{
    /**
     * @var string Error code (or message)
     */
    public $error = '';

    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var string ID to identify managed object
     */
    public $element = 'bank';

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'bank';

    /**
     * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
     */
    public $picto = 'accountline';

    /**
     * @var int ID
     */
    public $id;

    /**
     * @var string Ref
     */
    public $ref;

    /**
     * Date creation record (datec)
     *
     * @var integer
     */
    public $datec;

    /**
     * Date (dateo)
     *
     * @var integer
     */
    public $dateo;

    /**
     * Date value (datev)
     *
     * @var integer
     */
    public $datev;

    /**
     * @var float       Amount of payment in the bank account currency
     */
    public $amount;

    /**
     * @var float       Amount in the currency of company if bank account use another currency
     */
    public $amount_main_currency;

    /**
     * @var int         ID
     */
    public $fk_user_author;

    /**
     * @var int         ID
     */
    public $fk_user_rappro;

    /**
     * @var string      Type of operation (ex: "SOLD", "VIR", "CHQ", "CB", "PPL")
     */
    public $fk_type;

    /**
     * @var int         ID of cheque receipt
     */
    public $fk_bordereau;

    /**
     * @var int         ID of bank account
     */
    public $fk_account;

    /**
     * @var string      Ref of bank account
     */
    public $bank_account_ref;

    /**
     * @var string      Label of bank account
     */
    public $bank_account_label;

    /**
     * @var string      Bank account numero
     */
    public $numero_compte;

    /**
     * @var string      Name of check issuer
     */
    public $emetteur;

    /**
     * @var int<0,1>    1 if the line has been reconciled, 0 otherwise
     */
    public $rappro;

    /**
     * @var string      Name of the bank statement (if the line has been reconciled)
     */
    public $num_releve;

    /**
     * @var string      Cheque number
     */
    public $num_chq;

    /**
     * @var string      Bank name of the cheque
     */
    public $bank_chq;

    /**
     * @var string      Label of the bank transaction line
     */
    public $label;

    /**
     * @var string      Note
     */
    public $note;

    /**
     * User author of the reconciliation
     * TODO: variable used only by method info() => is it the same as $fk_user_rappro ?
     */
    public $user_rappro;


    /**
     *  Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     *  Load into memory content of a bank transaction line
     *
     * @param int    $rowid Id of bank transaction to load
     * @param string $ref   Ref of bank transaction to load
     * @param string $num   External num to load (ex: num of transaction for paypal fee)
     *
     * @return     int                 Return integer <0 if KO, 0 if OK but not found, >0 if OK and found
     */
    public function fetch($rowid, $ref = '', $num = '')
    {
        // Check parameters
        if (empty($rowid) && empty($ref) && empty($num)) {
            return -1;
        }

        $sql = "SELECT b.rowid, b.datec, b.datev, b.dateo, b.amount, b.label as label, b.fk_account,";
        $sql .= " b.fk_user_author, b.fk_user_rappro,";
        $sql .= " b.fk_type, b.num_releve, b.num_chq, b.rappro, b.note,";
        $sql .= " b.fk_bordereau, b.banque, b.emetteur,";
        $sql .= " ba.ref as bank_account_ref, ba.label as bank_account_label";
        $sql .= " FROM " . MAIN_DB_PREFIX . "bank as b,";
        $sql .= " " . MAIN_DB_PREFIX . "bank_account as ba";
        $sql .= " WHERE b.fk_account = ba.rowid";
        $sql .= " AND ba.entity IN (" . getEntity('bank_account') . ")";
        if ($num) {
            $sql .= " AND b.num_chq = '" . $this->db->escape($num) . "'";
        } elseif ($ref) {
            $sql .= " AND b.rowid = '" . $this->db->escape($ref) . "'";
        } else {
            $sql .= " AND b.rowid = " . ((int) $rowid);
        }

        dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result) {
            $ret = 0;

            $obj = $this->db->fetch_object($result);
            if ($obj) {
                $this->id = $obj->rowid;
                $this->rowid = $obj->rowid;
                $this->ref = $obj->rowid;

                $this->datec = $this->db->jdate($obj->datec);
                $this->datev = $this->db->jdate($obj->datev);
                $this->dateo = $this->db->jdate($obj->dateo);
                $this->amount = $obj->amount;
                $this->label = $obj->label;
                $this->note = $obj->note;

                $this->fk_user_author = $obj->fk_user_author;
                $this->fk_user_rappro = $obj->fk_user_rappro;

                $this->fk_type = $obj->fk_type; // Type of transaction
                $this->rappro = (int) $obj->rappro;
                $this->num_releve = $obj->num_releve;

                $this->num_chq = $obj->num_chq;
                $this->bank_chq = $obj->banque;
                $this->fk_bordereau = $obj->fk_bordereau;

                $this->fk_account = $obj->fk_account;
                $this->bank_account_ref = $obj->bank_account_ref;
                $this->bank_account_label = $obj->bank_account_label;

                // Retrieve all extrafield
                // fetch optionals attributes and labels
                $this->fetch_optionals();

                $ret = 1;
            }
            $this->db->free($result);
            return $ret;
        } else {
            return -1;
        }
    }

    /**
     * Inserts a transaction to a bank account
     *
     * @return int Return integer <0 if KO, rowid of the line if OK
     */
    public function insert()
    {
        $error = 0;

        $this->db->begin();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "bank (";
        $sql .= "datec";
        $sql .= ", dateo";
        $sql .= ", datev";
        $sql .= ", label";
        $sql .= ", amount";
        $sql .= ", amount_main_currency";
        $sql .= ", fk_user_author";
        $sql .= ", num_chq";
        $sql .= ", fk_account";
        $sql .= ", fk_type";
        $sql .= ", emetteur,banque";
        $sql .= ", rappro";
        $sql .= ", numero_compte";
        $sql .= ", num_releve";
        $sql .= ") VALUES (";
        $sql .= "'" . $this->db->idate($this->datec) . "'";
        $sql .= ", '" . $this->db->idate($this->dateo) . "'";
        $sql .= ", '" . $this->db->idate($this->datev) . "'";
        $sql .= ", '" . $this->db->escape($this->label) . "'";
        $sql .= ", " . price2num($this->amount);
        $sql .= ", " . (empty($this->amount_main_currency) ? "NULL" : price2num($this->amount_main_currency));
        $sql .= ", " . ($this->fk_user_author > 0 ? ((int) $this->fk_user_author) : "null");
        $sql .= ", " . ($this->num_chq ? "'" . $this->db->escape($this->num_chq) . "'" : "null");
        $sql .= ", '" . $this->db->escape($this->fk_account) . "'";
        $sql .= ", '" . $this->db->escape($this->fk_type) . "'";
        $sql .= ", " . ($this->emetteur ? "'" . $this->db->escape($this->emetteur) . "'" : "null");
        $sql .= ", " . ($this->bank_chq ? "'" . $this->db->escape($this->bank_chq) . "'" : "null");
        $sql .= ", " . (int) $this->rappro;
        $sql .= ", " . ($this->numero_compte ? "'" . $this->db->escape($this->numero_compte) . "'" : "''");
        $sql .= ", " . ($this->num_releve ? "'" . $this->db->escape($this->num_releve) . "'" : "null");
        $sql .= ")";


        dol_syslog(get_class($this) . "::insert", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . 'bank');
            // Actions on extra fields (by external module or standard code)
            $result = $this->insertExtraFields();
            if ($result < 0) {
                $error++;
            }
        } else {
            $error++;
            $this->error = $this->db->lasterror();
            dol_print_error($this->db);
        }

        if (!$error) {
            $this->db->commit();
            return $this->id;
        } else {
            $this->db->rollback();
            return -1 * $error;
        }
    }

    /**
     * Delete bank transaction record
     *
     * @param User|null $user      User object that delete
     * @param int       $notrigger 1=Does not execute triggers, 0= execute triggers
     *
     * @return  int                     Return integer <0 if KO, >0 if OK
     */
    public function delete(User $user = null, $notrigger = 0)
    {
        $nbko = 0;

        if ($this->rappro) {
            // Protection to avoid any delete of consolidated lines
            $this->error = "ErrorDeleteNotPossibleLineIsConsolidated";
            return -1;
        }

        $this->db->begin();

        if (!$notrigger) {
            // Call trigger
            $result = $this->call_trigger('BANKACCOUNTLINE_DELETE', $user);
            if ($result < 0) {
                $this->db->rollback();
                return -1;
            }
            // End call triggers
        }

        // Protection to avoid any delete of accounted lines. Protection on by default
        if (!getDolGlobalString('BANK_ALLOW_TRANSACTION_DELETION_EVEN_IF_IN_ACCOUNTING')) {
            $sql = "SELECT COUNT(rowid) as nb FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping WHERE doc_type = 'bank' AND fk_doc = " . ((int) $this->id);
            $resql = $this->db->query($sql);
            if ($resql) {
                $obj = $this->db->fetch_object($resql);
                if ($obj && $obj->nb) {
                    $this->error = 'ErrorRecordAlreadyInAccountingDeletionNotPossible';
                    $this->db->rollback();
                    return -1;
                }
            } else {
                $this->error = $this->db->lasterror();
                $this->db->rollback();
                return -1;
            }
        }

        // Delete urls
        $result = $this->delete_urls($user);
        if ($result < 0) {
            $nbko++;
        }

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "bank_class WHERE lineid=" . (int) $this->rowid;
        dol_syslog(get_class($this) . "::delete", LOG_DEBUG);
        $result = $this->db->query($sql);
        if (!$result) {
            $nbko++;
        }

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "bank_extrafields WHERE fk_object=" . (int) $this->rowid;
        $result = $this->db->query($sql);
        if (!$result) {
            $nbko++;
        }

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "bank WHERE rowid=" . (int) $this->rowid;
        dol_syslog(get_class($this) . "::delete", LOG_DEBUG);
        $result = $this->db->query($sql);
        if (!$result) {
            $nbko++;
        }

        if (!$nbko) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -$nbko;
        }
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

    /**
     *  Delete bank line records
     *
     * @param User|null $user User object that delete
     *
     * @return int                 Return integer <0 if KO, >0 if OK
     */
    public function delete_urls(User $user = null)
    {
        // phpcs:enable
        $nbko = 0;

        if ($this->rappro) {
            // Protection to avoid any delete of consolidated lines
            $this->error = "ErrorDeleteNotPossibleLineIsConsolidated";
            return -1;
        }

        $this->db->begin();

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "bank_url WHERE fk_bank=" . (int) $this->rowid;
        dol_syslog(get_class($this) . "::delete_urls", LOG_DEBUG);
        $result = $this->db->query($sql);
        if (!$result) {
            $nbko++;
        }

        if (!$nbko) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -$nbko;
        }
    }


    /**
     *      Update bank account record in database
     *
     * @param User $user      Object user making update
     * @param int  $notrigger 0=Disable all triggers
     *
     * @return int                     Return integer <0 if KO, >0 if OK
     */
    public function update(User $user, $notrigger = 0)
    {
        $this->db->begin();

        $sql = "UPDATE " . MAIN_DB_PREFIX . "bank SET";
        $sql .= " amount = " . price2num($this->amount) . ",";
        $sql .= " datev='" . $this->db->idate($this->datev) . "',";
        $sql .= " dateo='" . $this->db->idate($this->dateo) . "'";
        $sql .= " WHERE rowid = " . ((int) $this->rowid);

        dol_syslog(get_class($this) . "::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            $this->error = $this->db->error();
            return -1;
        }
    }


    /**
     *      Update bank account record label in database
     *
     * @return int                     Return integer <0 if KO, >0 if OK
     */
    public function updateLabel()
    {
        $this->db->begin();

        $sql = "UPDATE " . MAIN_DB_PREFIX . "bank SET";
        $sql .= " label = '" . $this->db->escape($this->label) . "'";
        $sql .= " WHERE rowid = " . ((int) $this->rowid);

        dol_syslog(get_class($this) . "::update_label", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            $this->error = $this->db->error();
            return -1;
        }
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

    /**
     *  Update conciliation field
     *
     * @param User $user        Object user making update
     * @param int  $cat         Category id
     * @param int  $conciliated 1=Set transaction to conciliated, 0=Keep transaction non conciliated
     *
     * @return int                     Return integer <0 if KO, >0 if OK
     */
    public function update_conciliation(User $user, $cat, $conciliated = 1)
    {
        // phpcs:enable
        global $conf, $langs;

        $this->db->begin();

        // Check statement field
        if (getDolGlobalString('BANK_STATEMENT_REGEX_RULE')) {
            if (!preg_match('/' . getDolGlobalString('BANK_STATEMENT_REGEX_RULE') . '/', $this->num_releve)) {
                $this->errors[] = $langs->trans("ErrorBankStatementNameMustFollowRegex", getDolGlobalString('BANK_STATEMENT_REGEX_RULE'));
                return -1;
            }
        }

        $sql = "UPDATE " . MAIN_DB_PREFIX . "bank SET";
        $sql .= " rappro = " . ((int) $conciliated);
        $sql .= ", num_releve = '" . $this->db->escape($this->num_releve) . "'";
        if ($conciliated) {
            $sql .= ", fk_user_rappro = " . $user->id;
        }
        $sql .= " WHERE rowid = " . ((int) $this->id);

        dol_syslog(get_class($this) . "::update_conciliation", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if (!empty($cat) && $cat > 0) {
                $sql = "INSERT INTO " . MAIN_DB_PREFIX . "bank_class (";
                $sql .= "lineid";
                $sql .= ", fk_categ";
                $sql .= ") VALUES (";
                $sql .= $this->id;
                $sql .= ", " . ((int) $cat);
                $sql .= ")";

                dol_syslog(get_class($this) . "::update_conciliation", LOG_DEBUG);
                $this->db->query($sql);

                // No error check. Can fail if category already affected
                // TODO Do no try the insert if link already exists
            }

            $this->rappro = 1;

            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

    /**
     *  Increase value date of a rowid
     *
     * @param int $id Id of line to change
     *
     * @return int             >0 if OK, 0 if KO
     */
    public function datev_next($id)
    {
        // phpcs:enable
        return $this->datev_change($id, 1);
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

    /**
     *  Increase/decrease value date of a rowid
     *
     * @param int $rowid Id of line
     * @param int $sign  1 or -1
     *
     * @return int                 >0 if OK, 0 if KO
     */
    public function datev_change($rowid, $sign = 1)
    {
        // phpcs:enable
        $sql = "SELECT datev FROM " . MAIN_DB_PREFIX . "bank WHERE rowid = " . ((int) $rowid);
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $newdate = $this->db->jdate($obj->datev) + (3600 * 24 * $sign);

            $sql = "UPDATE " . MAIN_DB_PREFIX . "bank SET";
            $sql .= " datev = '" . $this->db->idate($newdate) . "'";
            $sql .= " WHERE rowid = " . ((int) $rowid);

            $result = $this->db->query($sql);
            if ($result) {
                if ($this->db->affected_rows($result)) {
                    return 1;
                }
            } else {
                dol_print_error($this->db);
                return 0;
            }
        } else {
            dol_print_error($this->db);
        }
        return 0;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

    /**
     *  Decrease value date of a rowid
     *
     * @param int $id Id of line to change
     *
     * @return int             >0 if OK, 0 if KO
     */
    public function datev_previous($id)
    {
        // phpcs:enable
        return $this->datev_change($id, -1);
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

    /**
     *  Increase operation date of a rowid
     *
     * @param int $id Id of line to change
     *
     * @return int             >0 if OK, 0 if KO
     */
    public function dateo_next($id)
    {
        // phpcs:enable
        return $this->dateo_change($id, 1);
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

    /**
     *  Increase/decrease operation date of a rowid
     *
     * @param int $rowid Id of line
     * @param int $sign  1 or -1
     *
     * @return int                 >0 if OK, 0 if KO
     */
    public function dateo_change($rowid, $sign = 1)
    {
        // phpcs:enable
        $sql = "SELECT dateo FROM " . MAIN_DB_PREFIX . "bank WHERE rowid = " . ((int) $rowid);
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $newdate = $this->db->jdate($obj->dateo) + (3600 * 24 * $sign);

            $sql = "UPDATE " . MAIN_DB_PREFIX . "bank SET";
            $sql .= " dateo = '" . $this->db->idate($newdate) . "'";
            $sql .= " WHERE rowid = " . ((int) $rowid);

            $result = $this->db->query($sql);
            if ($result) {
                if ($this->db->affected_rows($result)) {
                    return 1;
                }
            } else {
                dol_print_error($this->db);
                return 0;
            }
        } else {
            dol_print_error($this->db);
        }
        return 0;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

    /**
     *  Decrease operation date of a rowid
     *
     * @param int $id Id of line to change
     *
     * @return int             >0 if OK, 0 if KO
     */
    public function dateo_previous($id)
    {
        // phpcs:enable
        return $this->dateo_change($id, -1);
    }


    /**
     *  Load miscellaneous information for tab "Info"
     *
     * @param int $id Id of object to load
     *
     * @return void
     */
    public function info($id)
    {
        $sql = 'SELECT b.rowid, b.datec, b.tms as datem,';
        $sql .= ' b.fk_user_author, b.fk_user_rappro';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'bank as b';
        $sql .= ' WHERE b.rowid = ' . ((int) $id);

        $result = $this->db->query($sql);
        if ($result) {
            if ($this->db->num_rows($result)) {
                $obj = $this->db->fetch_object($result);

                $this->id = $obj->rowid;

                $this->user_creation_id = $obj->fk_user_author;
                $this->user_rappro = $obj->fk_user_rappro;
                $this->date_creation = $this->db->jdate($obj->datec);
                $this->date_modification = $this->db->jdate($obj->datem);
                //$this->date_rappro       = $obj->daterappro;    // Not yet managed
            }
            $this->db->free($result);
        } else {
            dol_print_error($this->db);
        }
    }


    /**
     *      Return clickable name (with picto eventually)
     *
     * @param int    $withpicto 0=No picto, 1=Include picto into link, 2=Only picto
     * @param int    $maxlen    Longueur max libelle
     * @param string $option    Option ('', 'showall', 'showconciliated', 'showconciliatedandaccounted'). Options may
     *                          be slow.
     * @param int    $notooltip 1=Disable tooltip
     *
     * @return string                  Chaine avec URL
     */
    public function getNomUrl($withpicto = 0, $maxlen = 0, $option = '', $notooltip = 0)
    {
        global $conf, $langs;

        $result = '';

        $label = img_picto('', $this->picto) . ' <u>' . $langs->trans("BankTransactionLine") . '</u>:<br>';
        $label .= '<b>' . $langs->trans("Ref") . ':</b> ' . $this->ref;
        if ($this->amount) {
            $label .= '<br><strong>' . $langs->trans("Amount") . ':</strong> ' . price($this->amount, 0, $langs, 1, -1, -1, $conf->currency);
        }

        $linkstart = '<a href="' . DOL_URL_ROOT . '/compta/bank/line.php?rowid=' . ((int) $this->id) . '&save_lastsearch_values=1" title="' . dol_escape_htmltag($label, 1) . '" class="classfortooltip">';
        $linkend = '</a>';

        $result .= $linkstart;
        if ($withpicto) {
            $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'account'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
        }
        if ($withpicto != 2) {
            $result .= ($this->ref ? $this->ref : $this->id);
        }

        $result .= $linkend;

        if ($option == 'showall' || $option == 'showconciliated' || $option == 'showconciliatedandaccounted') {
            $result .= ' <span class="opacitymedium">(';
        }
        if ($option == 'showall') {
            $result .= $langs->trans("BankAccount") . ': ';
            $accountstatic = new Account($this->db);
            $accountstatic->id = $this->fk_account;
            $accountstatic->ref = $this->bank_account_ref;
            $accountstatic->label = $this->bank_account_label;
            $result .= $accountstatic->getNomUrl(0) . ', ';
        }
        if ($option == 'showall' || $option == 'showconciliated' || $option == 'showconciliatedandaccounted') {
            $result .= $langs->trans("BankLineConciliated") . ': ';
            $result .= yn($this->rappro);
        }
        if (isModEnabled('accounting') && ($option == 'showall' || $option == 'showconciliatedandaccounted')) {
            $sql = "SELECT COUNT(rowid) as nb FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping";
            $sql .= " WHERE doc_type = 'bank' AND fk_doc = " . ((int) $this->id);
            $resql = $this->db->query($sql);
            if ($resql) {
                $obj = $this->db->fetch_object($resql);
                if ($obj && $obj->nb) {
                    $result .= ' - ' . $langs->trans("Accounted") . ': ' . yn(1);
                } else {
                    $result .= ' - ' . $langs->trans("Accounted") . ': ' . yn(0);
                }
            }
        }
        if ($option == 'showall' || $option == 'showconciliated' || $option == 'showconciliatedandaccounted') {
            $result .= ')</span>';
        }

        return $result;
    }


    /**
     *  Return the label of the status
     *
     * @param int $mode 0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short
     *                  label + Picto, 6=Long label + Picto
     *
     * @return string                 Label of status
     */
    public function getLibStatut($mode = 0)
    {
        return $this->LibStatut($this->status, $mode);
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

    /**
     *  Return the label of a given status
     *
     * @param int $status Id status
     * @param int $mode   0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short
     *                    label + Picto, 6=Long label + Picto
     *
     * @return string                 Label of status
     */
    public function LibStatut($status, $mode = 0)
    {
        // phpcs:enable
        return '';
    }

    /**
     *  Return if a bank line was dispatched into bookkeeping
     *
     * @return     int         Return integer <0 if KO, 0=no, 1=yes
     */
    public function getVentilExportCompta()
    {
        $alreadydispatched = 0;

        $type = 'bank';

        $sql = " SELECT COUNT(ab.rowid) as nb FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab WHERE ab.doc_type='" . $this->db->escape($type) . "' AND ab.fk_doc = " . ((int) $this->id);
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj) {
                $alreadydispatched = $obj->nb;
            }
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }

        if ($alreadydispatched) {
            return 1;
        }
        return 0;
    }
}
