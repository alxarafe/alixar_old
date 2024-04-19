<?php

/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2005       Simon TOSSER            <simon@kornog-computing.com>
 * Copyright (C) 2013-2016  Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2013-2024	Alexandre Spangaro		<aspangaro@easya.solutions>
 * Copyright (C) 2014-2015	Ari Elbaz (elarifr)		<github@accedinfo.com>
 * Copyright (C) 2013-2021	Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2021      	Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2014       Juanjo Menent           <jmenent@2byte.es>s
 * Copyright (C) 2016       Laurent Destailleur     <eldy@users.sourceforge.net>
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

namespace DoliModules\Accounting\Controller;

global $conf;
global $db;
global $user;
global $hookmanager;
global $user;
global $menumanager;
global $langs;
global $mysoc;

use DoliCore\Base\DolibarrController;
use DoliCore\Form\FormAccounting;
use DoliModules\Accounting\Model\AccountingAccount;
use DoliCore\Form\Form;
use DoliCore\Form\FormOther;

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

class AccountingSupplierController extends DolibarrController
{
    /**
     * \file    htdocs/accountancy/supplier/card.php
     * \ingroup Accountancy (Double entries)
     * \brief   Card supplier ventilation
     */
    public function card()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("bills", "accountancy"));

        $action = GETPOST('action', 'aZ09');
        $cancel = GETPOST('cancel', 'alpha');
        $backtopage = GETPOST('backtopage', 'alpha');

        $codeventil = GETPOSTINT('codeventil');
        $id = GETPOSTINT('id');

// Security check
        if (!isModEnabled('accounting')) {
            accessforbidden();
        }
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'mouvements', 'lire')) {
            accessforbidden();
        }


        /*
         * Actions
         */

        if ($action == 'ventil' && $user->hasRight('accounting', 'bind', 'write')) {
            if (!$cancel) {
                if ($codeventil < 0) {
                    $codeventil = 0;
                }

                $sql = " UPDATE " . MAIN_DB_PREFIX . "facture_fourn_det";
                $sql .= " SET fk_code_ventilation = " . ((int) $codeventil);
                $sql .= " WHERE rowid = " . ((int) $id);

                $resql = $db->query($sql);
                if (!$resql) {
                    setEventMessages($db->lasterror(), null, 'errors');
                } else {
                    setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
                    if ($backtopage) {
                        header("Location: " . $backtopage);
                        exit();
                    }
                }
            } else {
                header("Location: ./lines.php");
                exit();
            }
        }

        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/supplier_card.php');

        $db->close();
    }

    /**
     * \file        htdocs/accountancy/supplier/index.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Home supplier journalization page
     */
    public function index(bool $executeActions = true): bool
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("compta", "bills", "other", "accountancy"));

        $validatemonth = GETPOSTINT('validatemonth');
        $validateyear = GETPOSTINT('validateyear');

// Security check
        if (!isModEnabled('accounting')) {
            accessforbidden();
        }
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'bind', 'write')) {
            accessforbidden();
        }

        $accountingAccount = new AccountingAccount($db);

        $month_start = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
        if (GETPOSTINT("year")) {
            $year_start = GETPOSTINT("year");
        } else {
            $year_start = dol_print_date(dol_now(), '%Y');
            if (dol_print_date(dol_now(), '%m') < $month_start) {
                $year_start--; // If current month is lower that starting fiscal month, we start last year
            }
        }
        $year_end = $year_start + 1;
        $month_end = $month_start - 1;
        if ($month_end < 1) {
            $month_end = 12;
            $year_end--;
        }
        $search_date_start = dol_mktime(0, 0, 0, $month_start, 1, $year_start);
        $search_date_end = dol_get_last_day($year_end, $month_end);
        $year_current = $year_start;

// Validate History
        $action = GETPOST('action', 'aZ09');

        $chartaccountcode = dol_getIdFromCode($db, getDolGlobalInt('CHARTOFACCOUNTS'), 'accounting_system', 'rowid', 'pcg_version');

// Security check
        if (!isModEnabled('accounting')) {
            accessforbidden();
        }
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'mouvements', 'lire')) {
            accessforbidden();
        }


        /*
         * Actions
         */

        if (($action == 'clean' || $action == 'validatehistory') && $user->hasRight('accounting', 'bind', 'write')) {
            // Clean database
            $db->begin();
            $sql1 = "UPDATE " . $db->prefix() . "facture_fourn_det as fd";
            $sql1 .= " SET fk_code_ventilation = 0";
            $sql1 .= ' WHERE fd.fk_code_ventilation NOT IN';
            $sql1 .= '	(SELECT accnt.rowid ';
            $sql1 .= "	FROM " . $db->prefix() . "accounting_account as accnt";
            $sql1 .= "	INNER JOIN " . $db->prefix() . "accounting_system as syst";
            $sql1 .= "	ON accnt.fk_pcg_version = syst.pcg_version AND syst.rowid = " . getDolGlobalInt('CHARTOFACCOUNTS') . " AND accnt.entity = " . ((int) $conf->entity) . ")";
            $sql1 .= " AND fd.fk_facture_fourn IN (SELECT rowid FROM " . $db->prefix() . "facture_fourn WHERE entity = " . ((int) $conf->entity) . ")";
            $sql1 .= " AND fk_code_ventilation <> 0";

            dol_syslog("htdocs/accountancy/customer/index.php fixaccountancycode", LOG_DEBUG);
            $resql1 = $db->query($sql1);
            if (!$resql1) {
                $error++;
                $db->rollback();
                setEventMessages($db->lasterror(), null, 'errors');
            } else {
                $db->commit();
            }
            // End clean database
        }

        if ($action == 'validatehistory') {
            $error = 0;
            $nbbinddone = 0;
            $nbbindfailed = 0;
            $notpossible = 0;

            $db->begin();

            // Now make the binding. Bind automatically only for product with a dedicated account that exists into chart of account, others need a manual bind
            // Supplier Invoice Lines (must be same request than into page list.php for manual binding)
            $sql = "SELECT f.rowid as facid, f.ref, f.ref_supplier, f.libelle as invoice_label, f.datef, f.type as ftype, f.fk_facture_source,";
            $sql .= " l.rowid, l.fk_product, l.description, l.total_ht, l.fk_code_ventilation, l.product_type as type_l, l.tva_tx as tva_tx_line, l.vat_src_code,";
            $sql .= " p.rowid as product_id, p.ref as product_ref, p.label as product_label, p.fk_product_type as type, p.tva_tx as tva_tx_prod,";
            if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
                $sql .= " ppe.accountancy_code_buy as code_buy, ppe.accountancy_code_buy_intra as code_buy_intra, ppe.accountancy_code_buy_export as code_buy_export,";
            } else {
                $sql .= " p.accountancy_code_buy as code_buy, p.accountancy_code_buy_intra as code_buy_intra, p.accountancy_code_buy_export as code_buy_export,";
            }
            $sql .= " aa.rowid as aarowid, aa2.rowid as aarowid_intra, aa3.rowid as aarowid_export, aa4.rowid as aarowid_thirdparty,";
            $sql .= " co.code as country_code, co.label as country_label,";
            $sql .= " s.tva_intra,";
            if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
                $sql .= " spe.accountancy_code_buy as company_code_buy";
            } else {
                $sql .= " s.accountancy_code_buy as company_code_buy";
            }
            $sql .= " FROM " . $db->prefix() . "facture_fourn as f";
            $sql .= " INNER JOIN " . $db->prefix() . "societe as s ON s.rowid = f.fk_soc";
            if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
                $sql .= " LEFT JOIN " . $db->prefix() . "societe_perentity as spe ON spe.fk_soc = s.rowid AND spe.entity = " . ((int) $conf->entity);
            }
            $sql .= " LEFT JOIN " . $db->prefix() . "c_country as co ON co.rowid = s.fk_pays ";
            $sql .= " INNER JOIN " . $db->prefix() . "facture_fourn_det as l ON f.rowid = l.fk_facture_fourn";
            $sql .= " LEFT JOIN " . $db->prefix() . "product as p ON p.rowid = l.fk_product";
            if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
                $sql .= " LEFT JOIN " . $db->prefix() . "product_perentity as ppe ON ppe.fk_product = p.rowid AND ppe.entity = " . ((int) $conf->entity);
            }
            $alias_societe_perentity = !getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED') ? "s" : "spe";
            $alias_product_perentity = !getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED') ? "p" : "ppe";
            $sql .= " LEFT JOIN " . $db->prefix() . "accounting_account as aa  ON " . $alias_product_perentity . ".accountancy_code_buy = aa.account_number         AND aa.active = 1  AND aa.fk_pcg_version = '" . $db->escape($chartaccountcode) . "' AND aa.entity = " . $conf->entity;
            $sql .= " LEFT JOIN " . $db->prefix() . "accounting_account as aa2 ON " . $alias_product_perentity . ".accountancy_code_buy_intra = aa2.account_number  AND aa2.active = 1 AND aa2.fk_pcg_version = '" . $db->escape($chartaccountcode) . "' AND aa2.entity = " . $conf->entity;
            $sql .= " LEFT JOIN " . $db->prefix() . "accounting_account as aa3 ON " . $alias_product_perentity . ".accountancy_code_buy_export = aa3.account_number AND aa3.active = 1 AND aa3.fk_pcg_version = '" . $db->escape($chartaccountcode) . "' AND aa3.entity = " . $conf->entity;
            $sql .= " LEFT JOIN " . $db->prefix() . "accounting_account as aa4 ON " . $alias_product_perentity . ".accountancy_code_buy = aa4.account_number        AND aa4.active = 1 AND aa4.fk_pcg_version = '" . $db->escape($chartaccountcode) . "' AND aa4.entity = " . $conf->entity;
            $sql .= " WHERE f.fk_statut > 0 AND l.fk_code_ventilation <= 0";
            $sql .= " AND l.product_type <= 2";
            $sql .= " AND f.entity IN (" . getEntity('facture_fourn', 0) . ")"; // We don't share object for accountancy
            if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
                $sql .= " AND f.datef >= '" . $db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) . "'";
            }
            if ($validatemonth && $validateyear) {
                $sql .= dolSqlDateFilter('f.datef', 0, $validatemonth, $validateyear);
            }

            dol_syslog('htdocs/accountancy/supplier/index.php');

            $result = $db->query($sql);
            if (!$result) {
                $error++;
                setEventMessages($db->lasterror(), null, 'errors');
            } else {
                $num_lines = $db->num_rows($result);

                $isBuyerInEEC = isInEEC($mysoc);

                $thirdpartystatic = new Societe($db);
                $facture_static = new FactureFournisseur($db);
                $facture_static_det = new SupplierInvoiceLine($db);
                $product_static = new Product($db);

                $i = 0;
                while ($i < min($num_lines, 10000)) {   // No more than 10000 at once
                    $objp = $db->fetch_object($result);

                    $thirdpartystatic->id = $objp->socid;
                    $thirdpartystatic->name = $objp->name;
                    $thirdpartystatic->client = $objp->client;
                    $thirdpartystatic->fournisseur = $objp->fournisseur;
                    $thirdpartystatic->code_client = $objp->code_client;
                    $thirdpartystatic->code_compta_client = $objp->code_compta_client;
                    $thirdpartystatic->code_fournisseur = $objp->code_fournisseur;
                    $thirdpartystatic->code_compta_fournisseur = $objp->code_compta_fournisseur;
                    $thirdpartystatic->email = $objp->email;
                    $thirdpartystatic->country_code = $objp->country_code;
                    $thirdpartystatic->tva_intra = $objp->tva_intra;
                    $thirdpartystatic->code_compta_product = $objp->company_code_buy;       // The accounting account for product stored on thirdparty object (for level3 suggestion)

                    $product_static->ref = $objp->product_ref;
                    $product_static->id = $objp->product_id;
                    $product_static->type = $objp->type;
                    $product_static->label = $objp->product_label;
                    $product_static->status = $objp->status;
                    $product_static->status_buy = $objp->status_buy;
                    $product_static->accountancy_code_sell = $objp->code_sell;
                    $product_static->accountancy_code_sell_intra = $objp->code_sell_intra;
                    $product_static->accountancy_code_sell_export = $objp->code_sell_export;
                    $product_static->accountancy_code_buy = $objp->code_buy;
                    $product_static->accountancy_code_buy_intra = $objp->code_buy_intra;
                    $product_static->accountancy_code_buy_export = $objp->code_buy_export;
                    $product_static->tva_tx = $objp->tva_tx_prod;

                    $facture_static->ref = $objp->ref;
                    $facture_static->id = $objp->facid;
                    $facture_static->type = $objp->ftype;
                    $facture_static->ref_supplier = $objp->ref_supplier;
                    $facture_static->label = $objp->invoice_label;
                    $facture_static->date = $db->jdate($objp->datef);
                    $facture_static->fk_facture_source = $objp->fk_facture_source;

                    $facture_static_det->id = $objp->rowid;
                    $facture_static_det->total_ht = $objp->total_ht;
                    $facture_static_det->tva_tx = $objp->tva_tx_line;
                    $facture_static_det->vat_src_code = $objp->vat_src_code;
                    $facture_static_det->product_type = $objp->type_l;
                    $facture_static_det->desc = $objp->description;

                    $accountingAccountArray = array(
                        'dom' => $objp->aarowid,
                        'intra' => $objp->aarowid_intra,
                        'export' => $objp->aarowid_export,
                        'thirdparty' => $objp->aarowid_thirdparty);

                    $code_buy_p_notset = '';
                    $code_buy_t_notset = '';

                    $suggestedid = 0;

                    $return = $accountingAccount->getAccountingCodeToBind($mysoc, $thirdpartystatic, $product_static, $facture_static, $facture_static_det, $accountingAccountArray, 'supplier');
                    if (!is_array($return) && $return < 0) {
                        setEventMessage($accountingAccount->error, 'errors');
                    } else {
                        $suggestedid = $return['suggestedid'];
                        $suggestedaccountingaccountfor = $return['suggestedaccountingaccountfor'];

                        if (!empty($suggestedid) && $suggestedaccountingaccountfor != '' && $suggestedaccountingaccountfor != 'eecwithoutvatnumber') {
                            $suggestedid = $return['suggestedid'];
                        } else {
                            $suggestedid = 0;
                        }
                    }

                    if ($suggestedid > 0) {
                        $sqlupdate = "UPDATE " . $db->prefix() . "facture_fourn_det";
                        $sqlupdate .= " SET fk_code_ventilation = " . ((int) $suggestedid);
                        $sqlupdate .= " WHERE fk_code_ventilation <= 0 AND product_type <= 2 AND rowid = " . ((int) $facture_static_det->id);

                        $resqlupdate = $db->query($sqlupdate);
                        if (!$resqlupdate) {
                            $error++;
                            setEventMessages($db->lasterror(), null, 'errors');
                            $nbbindfailed++;
                            break;
                        } else {
                            $nbbinddone++;
                        }
                    } else {
                        $notpossible++;
                        $nbbindfailed++;
                    }

                    $i++;
                }
                if ($num_lines > 10000) {
                    $notpossible += ($num_lines - 10000);
                }
            }

            if ($error) {
                $db->rollback();
            } else {
                $db->commit();
                setEventMessages($langs->trans('AutomaticBindingDone', $nbbinddone, $notpossible), null, ($notpossible ? 'warnings' : 'mesgs'));
                if ($nbbindfailed) {
                    setEventMessages($langs->trans('DoManualBindingForFailedRecord', $nbbindfailed), null, 'warnings');
                }
            }
        }

        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/supplier_index.php');

        $db->close();
    }

    /**
     * \file        htdocs/accountancy/supplier/lines.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Page of detail of the lines of ventilation of invoices suppliers
     */
    public function lines()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("compta", "bills", "other", "accountancy", "productbatch", "products"));

        $optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

        $account_parent = GETPOST('account_parent');
        $changeaccount = GETPOST('changeaccount');
// Search Getpost
        $search_societe = GETPOST('search_societe', 'alpha');
        $search_lineid = GETPOSTINT('search_lineid');
        $search_ref = GETPOST('search_ref', 'alpha');
        $search_invoice = GETPOST('search_invoice', 'alpha');
//$search_ref_supplier = GETPOST('search_ref_supplier', 'alpha');
        $search_label = GETPOST('search_label', 'alpha');
        $search_desc = GETPOST('search_desc', 'alpha');
        $search_amount = GETPOST('search_amount', 'alpha');
        $search_account = GETPOST('search_account', 'alpha');
        $search_vat = GETPOST('search_vat', 'alpha');
        $search_date_startday = GETPOSTINT('search_date_startday');
        $search_date_startmonth = GETPOSTINT('search_date_startmonth');
        $search_date_startyear = GETPOSTINT('search_date_startyear');
        $search_date_endday = GETPOSTINT('search_date_endday');
        $search_date_endmonth = GETPOSTINT('search_date_endmonth');
        $search_date_endyear = GETPOSTINT('search_date_endyear');
        $search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);   // Use tzserver
        $search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
        $search_country = GETPOST('search_country', 'alpha');
        $search_tvaintra = GETPOST('search_tvaintra', 'alpha');

// Load variable for pagination
        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : getDolGlobalString('ACCOUNTING_LIMIT_LIST_VENTILATION', $conf->liste_limit);
        $sortfield = GETPOST('sortfield', 'aZ09comma');
        $sortorder = GETPOST('sortorder', 'aZ09comma');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page < 0) {
            $page = 0;
        }
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        if (!$sortfield) {
            $sortfield = "f.datef, f.ref, l.rowid";
        }
        if (!$sortorder) {
            if (getDolGlobalInt('ACCOUNTING_LIST_SORT_VENTILATION_DONE') > 0) {
                $sortorder = "DESC";
            } else {
                $sortorder = "ASC";
            }
        }

        $formaccounting = new FormAccounting($db);

// Security check
        if (!isModEnabled('accounting')) {
            accessforbidden();
        }
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'mouvements', 'lire')) {
            accessforbidden();
        }


        $formaccounting = new FormAccounting($db);


        /*
         * Actions
         */

// Purge search criteria
        if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
            $search_societe = '';
            $search_lineid = '';
            $search_ref = '';
            $search_invoice = '';
            //$search_ref_supplier = '';
            $search_label = '';
            $search_desc = '';
            $search_amount = '';
            $search_account = '';
            $search_vat = '';
            $search_date_startday = '';
            $search_date_startmonth = '';
            $search_date_startyear = '';
            $search_date_endday = '';
            $search_date_endmonth = '';
            $search_date_endyear = '';
            $search_date_start = '';
            $search_date_end = '';
            $search_country = '';
            $search_tvaintra = '';
        }

        if (is_array($changeaccount) && count($changeaccount) > 0 && $user->hasRight('accounting', 'bind', 'write')) {
            $error = 0;

            if (!(GETPOSTINT('account_parent') >= 0)) {
                $error++;
                setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Account")), null, 'errors');
            }

            if (!$error) {
                $db->begin();

                $sql1 = "UPDATE " . MAIN_DB_PREFIX . "facture_fourn_det";
                $sql1 .= " SET fk_code_ventilation=" . (GETPOSTINT('account_parent') > 0 ? GETPOSTINT('account_parent') : '0');
                $sql1 .= ' WHERE rowid IN (' . $db->sanitize(implode(',', $changeaccount)) . ')';

                dol_syslog('accountancy/supplier/lines.php::changeaccount sql= ' . $sql1);
                $resql1 = $db->query($sql1);
                if (!$resql1) {
                    $error++;
                    setEventMessages($db->lasterror(), null, 'errors');
                }
                if (!$error) {
                    $db->commit();
                    setEventMessages($langs->trans("Save"), null, 'mesgs');
                } else {
                    $db->rollback();
                    setEventMessages($db->lasterror(), null, 'errors');
                }

                $account_parent = ''; // Protection to avoid to mass apply it a second time
            }
        }

        if (GETPOST('sortfield') == 'f.datef, f.ref, l.rowid') {
            $value = (GETPOST('sortorder') == 'asc,asc,asc' ? 0 : 1);
            require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
            $res = dolibarr_set_const($db, "ACCOUNTING_LIST_SORT_VENTILATION_DONE", $value, 'yesno', 0, '', $conf->entity);
        }

        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/supplier_lines.php');

        $db->close();
    }

    /**
     * \file        htdocs/accountancy/supplier/list.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Ventilation page from suppliers invoices
     */
    public function list()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("bills", "companies", "compta", "accountancy", "other", "productbatch", "products"));

        $action = GETPOST('action', 'aZ09');
        $massaction = GETPOST('massaction', 'alpha');
        $confirm = GETPOST('confirm', 'alpha');
        $toselect = GETPOST('toselect', 'array');
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'accountancysupplierlist'; // To manage different context of search
        $optioncss = GETPOST('optioncss', 'alpha');

        $default_account = GETPOSTINT('default_account');

// Select Box
        $mesCasesCochees = GETPOST('toselect', 'array');

// Search Getpost
        $search_societe = GETPOST('search_societe', 'alpha');
        $search_lineid = GETPOSTINT('search_lineid');
        $search_ref = GETPOST('search_ref', 'alpha');
        $search_ref_supplier = GETPOST('search_ref_supplier', 'alpha');
        $search_invoice = GETPOST('search_invoice', 'alpha');
        $search_label = GETPOST('search_label', 'alpha');
        $search_desc = GETPOST('search_desc', 'alpha');
        $search_amount = GETPOST('search_amount', 'alpha');
        $search_account = GETPOST('search_account', 'alpha');
        $search_vat = GETPOST('search_vat', 'alpha');
        $search_date_startday = GETPOSTINT('search_date_startday');
        $search_date_startmonth = GETPOSTINT('search_date_startmonth');
        $search_date_startyear = GETPOSTINT('search_date_startyear');
        $search_date_endday = GETPOSTINT('search_date_endday');
        $search_date_endmonth = GETPOSTINT('search_date_endmonth');
        $search_date_endyear = GETPOSTINT('search_date_endyear');
        $search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);   // Use tzserver
        $search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
        $search_country = GETPOST('search_country', 'alpha');
        $search_tvaintra = GETPOST('search_tvaintra', 'alpha');

// Load variable for pagination
        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : getDolGlobalString('ACCOUNTING_LIMIT_LIST_VENTILATION', $conf->liste_limit);
        $sortfield = GETPOST('sortfield', 'aZ09comma');
        $sortorder = GETPOST('sortorder', 'aZ09comma');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page < 0) {
            $page = 0;
        }
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        if (!$sortfield) {
            $sortfield = "f.datef, f.ref, l.rowid";
        }
        if (!$sortorder) {
            if (getDolGlobalInt('ACCOUNTING_LIST_SORT_VENTILATION_TODO') > 0) {
                $sortorder = "DESC";
            } else {
                $sortorder = "ASC";
            }
        }

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
        $hookmanager->initHooks(array('accountancysupplierlist'));

        $formaccounting = new FormAccounting($db);
        $accountingAccount = new AccountingAccount($db);

        $chartaccountcode = dol_getIdFromCode($db, getDolGlobalInt('CHARTOFACCOUNTS'), 'accounting_system', 'rowid', 'pcg_version');

// Security check
        if (!isModEnabled('accounting')) {
            accessforbidden();
        }
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'mouvements', 'lire')) {
            accessforbidden();
        }

// Define begin binding date
        if (empty($search_date_start) && getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
            $search_date_start = $db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING'));
        }


        /*
         * Actions
         */

        if (GETPOST('cancel', 'alpha')) {
            $action = 'list';
            $massaction = '';
        }
        if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
            $massaction = '';
        }

        $parameters = array();
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            // Purge search criteria
            if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
                $search_societe = '';
                $search_lineid = '';
                $search_ref = '';
                $search_ref_supplier = '';
                $search_invoice = '';
                $search_label = '';
                $search_desc = '';
                $search_amount = '';
                $search_account = '';
                $search_vat = '';
                $search_date_startday = '';
                $search_date_startmonth = '';
                $search_date_startyear = '';
                $search_date_endday = '';
                $search_date_endmonth = '';
                $search_date_endyear = '';
                $search_date_start = '';
                $search_date_end = '';
                $search_country = '';
                $search_tvaintra = '';
            }

            // Mass actions
            $objectclass = 'AccountingAccount';
            $permissiontoread = $user->hasRight('accounting', 'read');
            $permissiontodelete = $user->hasRight('accounting', 'delete');
            $uploaddir = $conf->accounting->dir_output;
            include DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';
        }


        if ($massaction == 'ventil' && $user->hasRight('accounting', 'bind', 'write')) {
            $msg = '';

            if (!empty($mesCasesCochees)) {
                $msg = '<div>' . $langs->trans("SelectedLines") . ': ' . count($mesCasesCochees) . '</div>';
                $msg .= '<div class="detail">';
                $cpt = 0;
                $ok = 0;
                $ko = 0;

                foreach ($mesCasesCochees as $maLigneCochee) {
                    $maLigneCourante = explode("_", $maLigneCochee);
                    $monId = $maLigneCourante[0];
                    $monCompte = GETPOST('codeventil' . $monId);

                    if ($monCompte <= 0) {
                        $msg .= '<div><span class="error">' . $langs->trans("Lineofinvoice") . ' ' . $monId . ' - ' . $langs->trans("NoAccountSelected") . '</span></div>';
                        $ko++;
                    } else {
                        $sql = " UPDATE " . MAIN_DB_PREFIX . "facture_fourn_det";
                        $sql .= " SET fk_code_ventilation = " . ((int) $monCompte);
                        $sql .= " WHERE rowid = " . ((int) $monId);

                        $accountventilated = new AccountingAccount($db);
                        $accountventilated->fetch($monCompte, '', 1);

                        dol_syslog('accountancy/supplier/list.php', LOG_DEBUG);
                        if ($db->query($sql)) {
                            $msg .= '<div><span class="green">' . $langs->trans("Lineofinvoice") . ' ' . $monId . ' - ' . $langs->trans("VentilatedinAccount") . ' : ' . length_accountg($accountventilated->account_number) . '</span></div>';
                            $ok++;
                        } else {
                            $msg .= '<div><span class="error">' . $langs->trans("ErrorDB") . ' : ' . $langs->trans("Lineofinvoice") . ' ' . $monId . ' - ' . $langs->trans("NotVentilatedinAccount") . ' : ' . length_accountg($accountventilated->account_number) . '<br> <pre>' . $sql . '</pre></span></div>';
                            $ko++;
                        }
                    }

                    $cpt++;
                }
                $msg .= '</div>';
                $msg .= '<div>' . $langs->trans("EndProcessing") . '</div>';
            }
        }

        if (GETPOST('sortfield') == 'f.datef, f.ref, l.rowid') {
            $value = (GETPOST('sortorder') == 'asc,asc,asc' ? 0 : 1);
            require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
            $res = dolibarr_set_const($db, "ACCOUNTING_LIST_SORT_VENTILATION_TODO", $value, 'yesno', 0, '', $conf->entity);
        }

        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/supplier_list.php');

        $db->close();
    }
}
